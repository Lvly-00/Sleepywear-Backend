<?php

namespace App\Http\Controllers;

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

        $orderRaw = "CASE
            WHEN status = 'Available' THEN 1
            WHEN status = 'Sold Out' THEN 2
            ELSE 3
        END";

        $items = Item::where('collection_id', $collectionId)
            ->with('collection')
            ->orderByRaw("$orderRaw ASC")
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $item->collection_name = $item->collection?->name ?? 'N/A';
                $item->is_available = $item->status === 'Available';
                $item->image_url = $item->image ? asset('storage/'.$item->image) : null;

                return $item;
            });

        return response()->json($items);
    }

    public function show($id)
    {
        $item = Item::with('collection')->find($id);

        if (! $item) {
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

        $collectionId = $validated['collection_id'];

        $lastItem = Item::where('collection_id', $collectionId)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastItem
            ? intval(substr($lastItem->code, strlen($collectionId))) + 1
            : 1;

        $code = $collectionId.str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

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

        $item->load('collection');
        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = true;
        $item->image_url = asset('storage/'.$item->image);

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
            'status' => 'nullable|string|in:Available,Sold Out',
        ]);

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
        if ($item->image && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return response()->noContent();
    }
}
