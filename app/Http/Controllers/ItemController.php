<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Item;
use Cloudinary\Cloudinary;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    // Helper function to get a configured Cloudinary instance
    private function getCloudinary()
    {
        // Use config() instead of env(). This is safe for caching.
        return new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $collectionId = $request->query('collection_id');

        if (! $collectionId) {
            return response()->json(['error' => 'collection_id is required'], 400);
        }

        // Get cloud name dynamically for URL construction
        $cloudName = config('services.cloudinary.cloud_name');

        $items = Item::where('collection_id', $collectionId)
            ->where('user_id', auth()->id())
            ->with('collection')
            ->get()
            ->map(function ($item) use ($cloudName) { // Pass cloudName to closure
                $item->collection_name = $item->collection?->name ?? 'N/A';
                $item->is_available = $item->status === 'Available';

                // Construct URL dynamically
                if ($item->image && ! str_starts_with($item->image, 'http')) {
                    $item->image_url = "https://res.cloudinary.com/{$cloudName}/image/upload/".$item->image;
                } else {
                    $item->image_url = $item->image;
                }

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

        if (! $item || $item->user_id !== auth()->id()) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $cloudName = config('services.cloudinary.cloud_name');

        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = $item->status === 'Available';

        if ($item->image && ! str_starts_with($item->image, 'http')) {
            $item->image_url = "https://res.cloudinary.com/{$cloudName}/image/upload/".$item->image;
        } else {
            $item->image_url = $item->image;
        }

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

        $collection = Collection::where('id', $validated['collection_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (! $collection) {
            return response()->json(['error' => 'Collection not found or access denied'], 404);
        }

        preg_match('/\d+/', $collection->name, $matches);
        $collectionNumber = str_pad($matches[0] ?? $collection->id, 2, '0', STR_PAD_LEFT);

        $lastItem = Item::where('collection_id', $collection->id)
            ->orderByDesc('id')
            ->first();

        $nextNumber = $lastItem
            ? intval(substr($lastItem->code, 3)) + 1
            : 1;

        $code = $collectionNumber.'-'.str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        try {
            $cloudinary = $this->getCloudinary();
            $uploadedFile = $request->file('image');

            $result = $cloudinary->uploadApi()->upload($uploadedFile->getRealPath(), [
                'folder' => 'items',
            ]);

            $publicId = $result['public_id'];
            $secureUrl = $result['secure_url'];

        } catch (\Exception $e) {
            return response()->json(['error' => 'Image upload failed: '.$e->getMessage()], 500);
        }

        $item = Item::create([
            'collection_id' => $collection->id,
            'user_id' => auth()->id(),
            'code' => $code,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'status' => 'Available',
            'capital' => $validated['capital'] ?? 0,
            'image' => $publicId,
        ]);

        $item->load('collection');
        $item->collection_name = $item->collection?->name ?? 'N/A';
        $item->is_available = true;
        $item->image_url = $secureUrl;

        return response()->json($item, 201);
    }

    public function update(Request $request, Item $item)
    {
        if ($item->user_id !== auth()->id()) {
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

        $collection = Collection::where('id', $validated['collection_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (! $collection) {
            return response()->json(['error' => 'Collection not found or access denied'], 404);
        }

        if ($request->hasFile('image')) {
            $cloudinary = $this->getCloudinary();

            if ($item->image && ! str_starts_with($item->image, 'http')) {
                try {
                    $cloudinary->uploadApi()->destroy($item->image);
                } catch (\Exception $e) {
                    // ignore
                }
            }

            try {
                $uploadedFile = $request->file('image');
                $result = $cloudinary->uploadApi()->upload($uploadedFile->getRealPath(), [
                    'folder' => 'items',
                ]);
                $item->image = $result['public_id'];
            } catch (\Exception $e) {
                return response()->json(['error' => 'Image upload failed'], 500);
            }
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

        $cloudName = config('services.cloudinary.cloud_name');

        if ($item->image && ! str_starts_with($item->image, 'http')) {
            $item->image_url = "https://res.cloudinary.com/{$cloudName}/image/upload/".$item->image;
        } else {
            $item->image_url = $item->image;
        }

        return response()->json($item);
    }

    public function destroy(Item $item)
    {
        if ($item->user_id !== auth()->id()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($item->image && ! str_starts_with($item->image, 'http')) {
            try {
                $cloudinary = $this->getCloudinary();
                $cloudinary->uploadApi()->destroy($item->image);
            } catch (\Exception $e) {
                // ignore
            }
        }

        $item->delete();

        return response()->noContent();
    }
}
