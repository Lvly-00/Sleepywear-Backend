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

        $items = Item::where('collection_id', $collectionId)
            ->orderByRaw("CASE WHEN status = 'Available' THEN 0 ELSE 1 END")
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'price' => $item->price,
                'status' => $item->status,
                'image_url' => $item->image ? asset('storage/'.$item->image) : null,
            ]);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:2048',
        ]);

        $collection = Collection::findOrFail($request->collection_id);

        // Extract numeric prefix from collection name
        preg_match('/^\d+/', $collection->name, $matches);
        $collectionNumber = isset($matches[0]) ? $matches[0] : '00';

        // Get last added item in this collection
        $lastItem = Item::where('collection_id', $collection->id)
            ->orderBy('created_at', 'desc') // use creation date
            ->first();

        $lastNumber = 0;
        if ($lastItem) {
            $lastNumber = intval(substr($lastItem->code, -2));
        }

        $itemCode = $collectionNumber.sprintf('%02d', $lastNumber + 1);

        $imagePath = $request->hasFile('image') ? $request->file('image')->store('items', 'public') : null;

        $item = Item::create([
            'collection_id' => $collection->id,
            'code' => $itemCode,
            'name' => $request->name,
            'price' => $request->price,
            'image' => $imagePath,
            'status' => 'Available',
        ]);

        $collection->increment('qty');
        $collection->increment('stock_qty');

        return response()->json($item, 201);
    }

    public function update(Request $request, Item $item)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:2048',
            'status' => 'nullable|in:Available,Sold Out',
        ]);

        if ($request->hasFile('image')) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $item->image = $request->file('image')->store('items', 'public');
        }

        if ($request->filled('status') && $request->status !== $item->status) {
            $collection = $item->collection;
            if ($request->status === 'Sold Out') {
                $collection->decrement('stock_qty');
            } else {
                $collection->increment('stock_qty');
            }
        }

        $item->update([
            'name' => $request->name,
            'price' => $request->price,
            'status' => $request->status ?? $item->status,
            'image' => $item->image,
        ]);

        return response()->json($item);
    }

    public function destroy(Item $item)
    {
        $collection = $item->collection;

        if ($item->status === 'Available') {
            $collection->decrement('stock_qty');
        }
        $collection->decrement('qty');

        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }
        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }
}
