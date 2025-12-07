<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB Facade

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $search = $request->input('search');

    // 1. Detect Database Driver
    $driver = DB::connection()->getDriverName(); // 'mysql', 'pgsql', 'sqlite'

    $query = Customer::where('user_id', auth()->id())
        ->orderBy('first_name');

    if ($search) {
        $query->where(function ($q) use ($search, $driver) {

            // --- PostgreSQL Strategy (Deployment) ---
            if ($driver === 'pgsql') {
                // Use ILIKE for case-insensitive search
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  // Postgres Concatenation
                  ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) ILIKE ?", ["%{$search}%"])
                  ->orWhere('contact_number', 'ILIKE', "%{$search}%");
            }

            // --- SQLite Strategy (Local/Testing) ---
            elseif ($driver === 'sqlite') {
                // SQLite uses || for concatenation
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhereRaw("(COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
                  ->orWhere('contact_number', 'LIKE', "%{$search}%");
            }

            // --- MySQL / MariaDB Strategy (Default) ---
            else {
                // MySQL uses CONCAT and is case-insensitive by default
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
                  ->orWhere('contact_number', 'LIKE', "%{$search}%");
            }
        });
    }

    $customers = $query->paginate($perPage);

    // Important: append search query for pagination links
    $customers->appends(['search' => $search]);

    // Add full_name attribute
    $customers->getCollection()->transform(function ($customer) {
        $customer->full_name = trim($customer->first_name.' '.$customer->last_name);
        return $customer;
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
            'address' => 'required|string|max:255',
            'social_handle' => 'nullable|string|max:255',
        ]);

        $validated['user_id'] = auth()->id();

        $customer = Customer::create($validated);
        $customer->full_name = trim($customer->first_name.' '.$customer->last_name);

        return response()->json($customer, 201);
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = Customer::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $customer) {
            return response()->json(['message' => 'Customer not found or unauthorized'], 404);
        }

        $customer->full_name = trim($customer->first_name.' '.$customer->last_name);

        return response()->json($customer);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $customer) {
            return response()->json(['message' => 'Customer not found or unauthorized'], 404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'social_handle' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);
        $customer->full_name = trim($customer->first_name.' '.$customer->last_name);

        return response()->json($customer);
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
