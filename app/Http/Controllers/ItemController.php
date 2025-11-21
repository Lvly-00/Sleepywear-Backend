<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $collectionId = $request->query('collection_id');

        if (! $collectionId) {
            return response()->json(['error' => 'collection_id is required'], 400);
        }

        // Filter items by authenticated user to prevent data leaks
        $items = Item::where('collection_id', $collectionId)
            ->where('user_id', auth()->id()) // <-- filter by current user
            ->with('collection')
            ->get()
            ->map(function ($item) {
                $item->collection_name = $item->collection?->name ?? 'N/A';
                $item->is_available = $item->status === 'Available';
                $item->image_url = $item->image ? asset('storage/'.$item->image) : null;

                return $item;
            })
            ->sort(function ($a, $b) {
                $statusSort = function ($status) {
                    return match ($status) {
                        'Available' => 1,
                        'Sold Out' => 2,
                        default => 3,
                    };
                };
                $aStatus = $statusSort($a->status);
                $bStatus = $statusSort($b->status);
                if ($aStatus !== $bStatus) {
                    return $aStatus <=> $bStatus;
                }
                if ($aStatus === 1 && $bStatus === 1) {
                    return $a->created_at <=> $b->created_at;
                }

                return $a->updated_at <=> $b->updated_at;
            })
            ->values();

        return response()->json($items);
    }

    public function show($id)
    {
        $item = Item::with('collection')->find($id);

        if (! $item || $item->user_id !== auth()->id()) { // protect access
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = $item->status === 'Available';
        $item->image_url = $item->image ? asset('storage/'.$item->image) : null;

        return response()->json($item);
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

        // Verify collection belongs to current user
        $collection = Collection::where('id', $validated['collection_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (! $collection) {
            return response()->json(['error' => 'Collection not found or access denied'], 404);
        }

        // Extract number from collection name, fallback to collection ID
        preg_match('/\d+/', $collection->name, $matches);
        $collectionNumber = str_pad($matches[0] ?? $collection->id, 2, '0', STR_PAD_LEFT);

        $lastItem = Item::where('collection_id', $collection->id)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastItem
            ? intval(substr($lastItem->code, 3)) + 1
            : 1;

        $code = $collectionNumber.'-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $path = $request->file('image')->store('items', 'public');

        $item = Item::create([
            'collection_id' => $collection->id,
            'user_id' => auth()->id(), // <-- save current user ID here
            'code' => $code,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'status' => 'Available',
            'capital' => $validated['capital'] ?? 0,
            'image' => $path,
        ]);

        $item->load('collection');
        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = true;
        $item->image_url = asset('storage/'.$item->image);

        return response()->json($item, 201);
    }

    public function update(Request $request, Item $item)
    {
        if ($item->user_id !== auth()->id()) { // protect update
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'capital' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'status' => 'nullable|string|in:Available,Sold Out',
        ]);

        // Verify collection belongs to current user
        $collection = Collection::where('id', $validated['collection_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (! $collection) {
            return response()->json(['error' => 'Collection not found or access denied'], 404);
        }

        if ($request->hasFile('image')) {
            if ($item->image && Storage::disk('public')->exists($item->image)) {
                Storage::disk('public')->delete($item->image);
            }
            $path = $request->file('image')->store('items', 'public');
            $item->image = $path;
        }

        $item->update([
            'collection_id' => $collection->id,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'capital' => $validated['capital'] ?? 0,
            'status' => $validated['status'] ?? $item->status,
        ]);

        $item->load('collection');
        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = $item->status === 'Available';
        $item->image_url = $item->image ? asset('storage/'.$item->image) : null;

        return response()->json($item);
    }

    public function destroy(Item $item)
    {
        if ($item->user_id !== auth()->id()) { // protect delete
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($item->image && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return response()->noContent();
    }
}
