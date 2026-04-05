<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB Facade
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $driver = DB::connection()->getDriverName();

        $query = Customer::with('orders.invoice')
            ->where('user_id', auth()->id())
            ->orderBy('first_name', 'asc')
            ->orderBy('id', 'asc');

        if ($search) {
            $query->where(function ($q) use ($search, $driver) {
                if ($driver === 'pgsql') {
                    $q->where('first_name', 'ILIKE', "%{$search}%")
                        ->orWhere('last_name', 'ILIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) ILIKE ?", ["%{$search}%"])
                        ->orWhere('contact_number', 'ILIKE', "%{$search}%");
                } elseif ($driver === 'sqlite') {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhereRaw("(COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
                        ->orWhere('contact_number', 'LIKE', "%{$search}%");
                } else {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
                        ->orWhere('contact_number', 'LIKE', "%{$search}%");
                }
            });
        }

        // Execute Cursor Pagination
        $customers = $query->cursorPaginate($perPage);

        // Transform data
        $customers->getCollection()->transform(function ($customer) {
            return [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'full_name' => trim($customer->first_name.' '.$customer->last_name),
                // REMOVED: 'address' => $customer->address, (This was the old column causing 500 errors)
                'contact_number' => $customer->contact_number,
                'social_handle' => $customer->social_handle,
                'orders' => $customer->orders,
                'addresses' => $customer->addresses->pluck('address'), // New relationship
                'address' => $customer->addresses->first()?->address ?? 'No address set', // Default for display
                'created_at' => $customer->created_at,
            ];
        });

        return response()->json($customers);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'social_handle' => 'nullable|string|max:255',
            'addresses' => 'required|array|min:1', // Expecting an array
            'addresses.*' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Create the customer (Excluding address data)
            $customer = Customer::create([
                'user_id' => auth()->id(),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'contact_number' => $validated['contact_number'],
                'social_handle' => $validated['social_handle'],
            ]);

            // 2. Create entries in the 'customer_addresses' table
            foreach ($validated['addresses'] as $index => $addressText) {
                $customer->addresses()->create([
                    'address' => $addressText,
                    'is_default' => $index === 0, // First one is default
                ]);
            }

            return response()->json($customer->load('addresses'), 201);
        });
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = Customer::with(['orders.invoice' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->full_name = trim($customer->first_name.' '.$customer->last_name);

        return response()->json($customer);
    }

    /**
     * Update the specified customer.
     */
    // public function update(Request $request, $id)
    // {
    //     $customer = Customer::where('id', $id)
    //         ->where('user_id', auth()->id())
    //         ->first();

    //     if (! $customer) {
    //         return response()->json(['message' => 'Customer not found or unauthorized'], 404);
    //     }

    //     $validated = $request->validate([
    //         'first_name' => 'required|string|max:255',
    //         'last_name' => 'nullable|string|max:255',
    //         'contact_number' => 'nullable|string|max:255',
    //         'address' => 'required|string|max:255',
    //         'social_handle' => 'nullable|string|max:255',
    //     ]);

    //     $customer->update($validated);
    //     $customer->full_name = trim($customer->first_name.' '.$customer->last_name);

    //     return response()->json($customer);
    // }

    public function update(Request $request, $id)
    {
        $customer = Customer::where('id', $id)->where('user_id', auth()->id())->first();

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:255',
            'social_handle' => 'nullable|string|max:255',
            'addresses' => 'required|array|min:1',
            'addresses.*' => 'required|string',
        ]);

        // 1. Handle Profile Picture Upload
        // if ($request->hasFile('profile_image')) {
        //     // Delete old image if exists
        //     if ($customer->profile_picture) {
        //         Storage::disk('public')->delete($customer->profile_picture);
        //     }
        //     $path = $request->file('profile_image')->store('customers/profiles', 'public');
        //     $customer->profile_picture = $path;
        // }

        // 2. Update Basic Info
        $customer->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'contact_number' => $validated['contact_number'],
            'social_handle' => $validated['social_handle'],
        ]);

        // 3. Sync Addresses (Simple approach: Delete old and re-insert)
        $customer->addresses()->delete();
        foreach ($validated['addresses'] as $index => $addressText) {
            $customer->addresses()->create([
                'address' => $addressText,
                'is_default' => $index === 0, // First address is default
            ]);
        }

        return response()->json([
            'message' => 'Customer details are updated successfully.',
            'customer' => $customer->load('addresses'),
        ]);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy($id)
    {
        $customer = Customer::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $customer) {
            return response()->json(['message' => 'Customer not found or unauthorized'], 404);
        }

        $customer->delete();

        return response()->json(['message' => 'Customer deleted']);
    }
}
