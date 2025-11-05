<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request)
{
    $collectionId = $request->query('collection_id');

    if (!$collectionId) {
        return response()->json(['error' => 'collection_id is required'], 400);
    }

    $cacheKey = "items_collection_{$collectionId}";
    $ttl = now()->addMinutes(5);

    // Helper query to prioritize status
    $orderRaw = "CASE
        WHEN status = 'Available' THEN 1
        WHEN status = 'Sold Out' THEN 2
        ELSE 3
    END";

    if (Cache::has("skip_cache_{$collectionId}")) {
        $items = Item::where('collection_id', $collectionId)
            ->with('collection')
            ->orderByRaw("$orderRaw ASC")
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $item->collection_name = $item->collection?->name ?? 'N/A';
                $item->is_available = $item->status === 'Available';
                $item->image_url = $item->image ? asset('storage/' . $item->image) : null;
                return $item;
            });

        return response()->json($items);
    }

    $items = Cache::remember($cacheKey, $ttl, function () use ($collectionId, $orderRaw) {
        return Item::where('collection_id', $collectionId)
            ->with('collection')
            ->orderByRaw("$orderRaw ASC")
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $item->collection_name = $item->collection?->name ?? 'N/A';
                $item->is_available = $item->status === 'Available';
                $item->image_url = $item->image ? asset('storage/' . $item->image) : null;
                return $item;
            });
    });

    return response()->json($items);
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'capital' => 'nullable|numeric|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $collectionId = $validated['collection_id'];

        $lastItem = Item::where('collection_id', $collectionId)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastItem
            ? intval(substr($lastItem->code, strlen($collectionId))) + 1
            : 1;

        $code = $collectionId . str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

        $path = $request->file('image')->store('items', 'public');

        $item = Item::create([
            'collection_id' => $collectionId,
            'code' => $code,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'status' => 'Available',
            'capital' => $validated['capital'] ?? 0,
            'image' => $path,
        ]);

        // Invalidate cache for real-time update
        Cache::forget("items_collection_{$collectionId}");
        Cache::put("skip_cache_{$collectionId}", true, now()->addSeconds(5));

        $item->load('collection');
        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = true;
        $item->image_url = asset('storage/' . $item->image);

        return response()->json($item, 201);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'capital' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $oldCollectionId = $item->collection_id;

        if ($request->hasFile('image')) {
            if ($item->image && Storage::disk('public')->exists($item->image)) {
                Storage::disk('public')->delete($item->image);
            }

            $path = $request->file('image')->store('items', 'public');
            $item->image = $path;
        }

        $item->update([
            'collection_id' => $validated['collection_id'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'capital' => $validated['capital'] ?? 0,
        ]);

        // Clear cache for both collections
        Cache::forget("items_collection_{$oldCollectionId}");
        Cache::forget("items_collection_{$item->collection_id}");
        Cache::put("skip_cache_{$item->collection_id}", true, now()->addSeconds(5));

        $item->load('collection');
        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = $item->status === 'Available';
        $item->image_url = $item->image ? asset('storage/' . $item->image) : null;

        return response()->json($item);
    }

    public function destroy(Item $item)
    {
        $collectionId = $item->collection_id;

        // Delete the image if exists
        if ($item->image && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        Cache::forget("items_collection_{$collectionId}");
        Cache::forget("item_{$item->id}");
        Cache::put("skip_cache_{$collectionId}", true, now()->addSeconds(5));

        return response()->noContent();
    }
}
