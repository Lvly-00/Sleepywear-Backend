<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;



class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::with('items')->get()->map(function ($col) {
            // Total stock and sales
            $col->stock_qty = $col->items->sum('collection_stock_qty');
            $col->qty = $col->items->count();
            $col->total_sales = $col->items->where('status', 'taken')->count();

            // Status: Active if any item available, Sold Out if none
            $col->status = $col->items->where('status', 'available')->count() > 0 ? 'Active' : 'Sold Out';
            return $col;
        });

        return response()->json($collections);
    }



    public function store(Request $request)
    {
        return Collection::create($request->all());
    }

    public function show(Collection $collection)
    {
        return $collection->load('items');
    }

    public function update(Request $request, Collection $collection)
    {
        $collection->update($request->all());
        return $collection;
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();
        return response()->noContent();
    }
}
