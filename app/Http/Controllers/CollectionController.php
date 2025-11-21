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

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        // Query only this user's collections
        $query = Collection::with('items')
            ->where('user_id', auth()->id())
            ->orderBy('id', 'asc');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $collections = $query->paginate($perPage);

        $collections->getCollection()->transform(function ($col) {
            if (is_numeric($col->name)) {
                $col->name = $this->ordinal($col->name);
            }

            $col->stock_qty = $col->items->sum('stock_qty');
            $col->qty = $col->items->count();
            $col->total_sales = $col->items->where('status', 'Sold Out')->sum('price');
            $col->capital = $col->capital ?? 0;
            $col->status = $col->items->where('status', 'Available')->count() > 0
                ? 'Active'
                : 'Sold Out';

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
        $finalName = is_numeric($inputName) ? $this->ordinal($inputName) : $inputName;

        // Prevent duplicate per user
        if (
            Collection::where('user_id', auth()->id())
                ->where('name', $finalName)
                ->exists()
        ) {
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
            'user_id' => auth()->id(),
        ]);

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
        // Prevent viewing another user's collection
        if ($collection->user_id !== auth()->id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

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

        return response()->json($collection);
    }

    public function update(Request $request, Collection $collection)
    {
        if ($collection->user_id !== auth()->id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255', // numeric string expected
            'release_date' => 'required|date',
            'capital' => 'required|numeric|min:0',
        ]);

        $inputName = $request->input('name');

        if (is_numeric($inputName)) {
            $finalName = $this->ordinal((int) $inputName);
        } else {
            $finalName = $inputName;
        }

        if (
            Collection::where('user_id', auth()->id())
                ->where('id', '!=', $collection->id)
                ->where('name', $finalName)
                ->exists()
        ) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'name' => ['The collection name has already been taken.'],
                ],
            ], 422);
        }

        $collection->update([
            'name' => $finalName,
            'release_date' => $request->input('release_date'),
            'capital' => $request->input('capital'),
        ]);

        $collection->load('items');
        preg_match('/\d+/', $collection->name, $matches);
        $collection->ordinal = ! empty($matches) ? (int) $matches[0] : null;

        $collection->stock_qty = $collection->items->sum('stock_qty');
        $collection->qty = $collection->items->count();
        $collection->total_sales = $collection->items->where('status', 'Sold Out')->sum('price');
        $collection->status = $collection->items->where('status', 'Available')->count() > 0 ? 'Active' : 'Sold Out';

        return response()->json($collection);
    }

    public function destroy(Collection $collection)
    {
        // Protect delete
        if ($collection->user_id !== auth()->id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $collection->delete();

        return response()->noContent();
    }
}
