<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    private function ordinal($number)
    {
        $suffix = 'th';
        if (! in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1: $suffix = 'st';
                    break;
                case 2: $suffix = 'nd';
                    break;
                case 3: $suffix = 'rd';
                    break;
            }
        }

        return $number.$suffix.' Collection';
    }

    public function index()
    {
        $collections = Collection::with('items')->get()->map(function ($col) {
            if (is_numeric($col->name)) {
                $col->name = $this->ordinal($col->name);
            }

            $col->stock_qty = $col->sum('stock_qty');
            $col->qty = $col->items->count();
            $col->total_sales = $col->items
                ->where('status', 'Sold Out')
                ->sum('price');
            $col->capital = $col->capital ?? 0;
            $col->status = $col->items->where('status', 'Available')->count() > 0 ? 'Active' : 'Sold Out';

            return $col;
        });

        return response()->json($collections);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'capital' => 'required|numeric|min:0',
        ]);

        $inputName = $request->input('name');
        if (is_numeric($inputName)) {
            $finalName = $this->ordinal($inputName);
        } else {
            $finalName = $inputName;
        }

        $exists = Collection::where('name', $finalName)->exists();
        if ($exists) {
            // Return Laravel validation error style
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'name' => ['The collection name has already been taken.'],
                ],
            ], 422);
        }

        $collection = Collection::create([
            'name' => $finalName,
            'release_date' => $request->input('release_date'),
            'capital' => $request->input('capital'),
        ]);

        $collection->load('items');
        $collection->stock_qty = $collection->items->sum('collection_stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'Sold Out')
            ->sum('price');
        $collection->status = $collection->items->where('status', 'Available')->count() > 0 ? 'Active' : 'Sold Out';

        return response()->json($collection, 201);
    }

    public function show(Collection $collection)
    {
        $collection->load('items');
        $collection->stock_qty = $collection->sum('stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'Sold Out')
            ->sum('price');
        $collection->capital = $collection->items->sum('capital');
        $collection->status = $collection->items->where('status', 'Available')->count() > 0 ? 'Active' : 'Sold Out';

        if (is_numeric($collection->name)) {
            $collection->name = $this->ordinal($collection->name);
        }

        return response()->json($collection);
    }

    public function update(Request $request, Collection $collection)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'capital' => 'required|numeric|min:0',
        ]);

        $collection->update($request->only('name', 'release_date', 'capital'));

        $collection->load('items');
        $collection->stock_qty = $collection->sum('stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'Sold Out')
            ->sum('price');
        $collection->status = $collection->items->where('status', 'Available')->count() > 0 ? 'Active' : 'Sold Out';

        if (is_numeric($collection->name)) {
            $collection->name = $this->ordinal($collection->name);
        }

        return response()->json($collection);
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();

        return response()->noContent();
    }
}
