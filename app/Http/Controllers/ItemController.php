<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    // 🔹 List all items, with optional filtering by collection
    public function index(Request $request)
    {
        $collectionId = $request->query('collection_id');
        $items = Item::where('collection_id', $collectionId)
            ->orderByRaw("CASE WHEN status = 'available' THEN 0 WHEN status = 'taken' THEN 1 ELSE 2 END ")
            ->get()
            ->map(function ($item) {
                $item->image_url = $item->image ? asset('storage/' . $item->image) : null;
                return $item;
            });

        return response()->json($items);
    }

    // 🔹 Store a new item
    public function store(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        // Generate incremental code
        $lastItem = Item::where('collection_id', $request->collection_id)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastItem ? intval(substr($lastItem->code, -3)) + 1 : 1;
        $itemCode = sprintf("COLL-%s-%03d", $request->collection_id, $nextNumber);

        // Save image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('items', 'public');
        }

        $item = Item::create([
            'collection_id' => $request->collection_id,
            'code' => $itemCode,
            'name' => $request->name,
            'price' => $request->price,
            'notes' => $request->notes,
            'image' => $imagePath,
            'status' => 'available',

        ]);

        $item->image_url = $item->image ? asset('storage/' . $item->image) : null;

        return response()->json($item, 201);
    }

    // 🔹 Show item details
    public function show(Item $item)
    {
        $item->image_url = $item->image ? asset('storage/' . $item->image) : null;
        return response()->json($item);
    }

    // 🔹 Update an item
    public function update(Request $request, Item $item)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        // Replace image if new one uploaded
        if ($request->hasFile('image')) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $item->image = $request->file('image')->store('items', 'public');
        }

        $item->update([
            'name' => $request->name,
            'price' => $request->price,
            'notes' => $request->notes,
            'image' => $item->image,
        ]);

        $item->image_url = $item->image ? asset('storage/' . $item->image) : null;

        return response()->json($item);
    }

    // 🔹 Delete an item
    public function destroy(Item $item)
    {
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }
}
