<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    // 🔹 Fetch all collections with totals and status
    public function index()
    {
        $collections = Collection::with('items')->get()->map(function ($col) {
            $col->stock_qty = $col->items->sum('collection_stock_qty');
            $col->qty = $col->items->count();
            $col->total_sales = $col->items
                ->where('status', 'taken')
                ->sum('price');
            $col->capital = $col->capital ?? 0;
            $col->status = $col->items->where('status', 'available')->count() > 0 ? 'Active' : 'Sold Out';
            return $col;
        });

        return response()->json($collections);
    }

    // 🔹 Store a new collection and return computed totals/status
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'capital' => 'required|numeric|min:0',
        ]);

        $collection = Collection::create($request->only('name', 'release_date', 'capital'));

        // Initialize totals
        $collection->load('items');
        $collection->stock_qty = $collection->items->sum('collection_stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'taken')
            ->sum('price');
        $collection->capital = $collection->items->sum('capital');
        $collection->status = $collection->items->where('status', 'available')->count() > 0 ? 'Active' : 'Sold Out';

        return response()->json($collection, 201);
    }

    // 🔹 Show a collection with items
    public function show(Collection $collection)
    {
        $collection->load('items');
        $collection->stock_qty = $collection->items->sum('collection_stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'taken')
            ->sum('price');
        $collection->capital = $collection->items->sum('capital');
        $collection->status = $collection->items->where('status', 'available')->count() > 0 ? 'Active' : 'Sold Out';

        return response()->json($collection);
    }

    // Update a collection
    public function update(Request $request, Collection $collection)
    {
        $collection->update($request->only('name', 'release_date', 'capital'));

        $collection->load('items');
        $collection->stock_qty = $collection->items->sum('collection_stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'taken')
            ->sum('price');
        $collection->capital = $collection->items->sum('capital');

        $collection->status = $collection->items->where('status', 'available')->count() > 0 ? 'Active' : 'Sold Out';

        return response()->json($collection);
    }

    // 🔹 Delete a collection
    public function destroy(Collection $collection)
    {
        $collection->delete();
        return response()->noContent();
    }
}
