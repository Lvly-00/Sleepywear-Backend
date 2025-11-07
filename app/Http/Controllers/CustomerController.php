<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     * Supports optional search filtering.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        $search = $request->query('search');

        if ($search) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(contact_number) LIKE ?', ["%{$searchLower}%"])
                    ->orWhereRaw('LOWER(social_handle) LIKE ?', ["%{$searchLower}%"]);
            });
        }

        $customers = $query->get()->map(function ($customer) {
            $customer->full_name = trim($customer->first_name . ' ' . $customer->last_name);
            return $customer;
        })->sortBy('full_name')->values();

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

        $customer = Customer::create($validated);
        $customer->full_name = trim($customer->first_name . ' ' . $customer->last_name);

        return response()->json($customer, 201);
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->full_name = trim($customer->first_name . ' ' . $customer->last_name);

        return response()->json($customer);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'address' => 'required|string|max:255',
            'social_handle' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);
        $customer->full_name = trim($customer->first_name . ' ' . $customer->last_name);

        return response()->json($customer);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted']);
    }
}
