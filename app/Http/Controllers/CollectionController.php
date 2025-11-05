<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CollectionController extends Controller
{
    private function ordinal($number)
    {
        $suffix = 'th';
        if (! in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1: $suffix = 'st'; break;
                case 2: $suffix = 'nd'; break;
                case 3: $suffix = 'rd'; break;
            }
        }

        return $number . $suffix . ' Collection';
    }

  public function index()
{
    $cacheKey = 'collections_with_items';
    $ttl = now()->addMinutes(5);

    $collections = Cache::remember($cacheKey, $ttl, function () {
        return Collection::with('items')
            ->get()
            ->map(function ($col) {
                if (is_numeric($col->name)) {
                    $col->name = $this->ordinal($col->name);
                }

                $col->stock_qty = $col->items->sum('stock_qty');
                $col->qty = $col->items->count();
                $col->total_sales = $col->items
                    ->where('status', 'Sold Out')
                    ->sum('price');
                $col->capital = $col->capital ?? 0;
                $col->status = $col->items->where('status', 'Available')->count() > 0
                    ? 'Active'
                    : 'Sold Out';

                return $col;
            })
            ->sort(function ($a, $b) {
                if ($a->status === 'Active' && $b->status !== 'Active') {
                    return -1;
                }
                if ($a->status !== 'Active' && $b->status === 'Active') {
                    return 1;
                }

                if ($a->status === 'Active' && $b->status === 'Active') {
                    return $b->created_at <=> $a->created_at;
                }

                return $a->updated_at <=> $b->updated_at;
            })
            ->values();
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
        $finalName = is_numeric($inputName) ? $this->ordinal($inputName) : $inputName;

        if (Collection::where('name', $finalName)->exists()) {
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

        Cache::forget('collections_with_items');

        $collection->load('items');
        $collection->stock_qty = $collection->items->sum('stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'Sold Out')
            ->sum('price');
        $collection->status = $collection->items->where('status', 'Available')->count() > 0
            ? 'Active'
            : 'Sold Out';

        return response()->json($collection, 201);
    }

    public function show(Collection $collection)
    {
        $cacheKey = "collection_{$collection->id}";
        $ttl = now()->addMinutes(5);

        $collectionData = Cache::remember($cacheKey, $ttl, function () use ($collection) {
            $collection->load('items');
            $collection->stock_qty = $collection->items->sum('stock_qty');
            $collection->qty = $collection->items->count();
            $collection->total_sales = $collection->items
                ->where('status', 'Sold Out')
                ->sum('price');
            $collection->capital = $collection->items->sum('capital');
            $collection->status = $collection->items->where('status', 'Available')->count() > 0
                ? 'Active'
                : 'Sold Out';

            if (is_numeric($collection->name)) {
                $collection->name = $this->ordinal($collection->name);
            }

            return $collection;
        });

        return response()->json($collectionData);
    }

    public function update(Request $request, Collection $collection)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'capital' => 'required|numeric|min:0',
        ]);

        $collection->update($request->only('name', 'release_date', 'capital'));

        Cache::forget('collections_with_items');
        Cache::forget("collection_{$collection->id}");

        $collection->load('items');
        $collection->stock_qty = $collection->items->sum('stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items
            ->where('status', 'Sold Out')
            ->sum('price');
        $collection->status = $collection->items->where('status', 'Available')->count() > 0
            ? 'Active'
            : 'Sold Out';

        if (is_numeric($collection->name)) {
            $collection->name = $this->ordinal($collection->name);
        }

        return response()->json($collection);
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();

        Cache::forget('collections_with_items');
        Cache::forget("collection_{$collection->id}");

        return response()->noContent();
    }
}
