<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     * Uses cache for the full list, filters client-side.
     */
    public function index(Request $request)
    {
        $cacheKey = 'customers_list';
        $ttl = now()->addMinutes(5);

        // Cache the full customers list with computed full_name, sorted
        $customers = Cache::remember($cacheKey, $ttl, function () {
            return Customer::all()
                ->map(function ($customer) {
                    $customer->full_name = trim($customer->first_name . ' ' . $customer->last_name);
                    return $customer;
                })
                ->sortBy('full_name')
                ->values();
        });

        $search = $request->query('search');

        // Filter the cached collection by search query if provided
        if ($search) {
            $searchLower = strtolower($search);
            $customers = $customers->filter(function ($customer) use ($searchLower) {
                return str_contains(strtolower($customer->first_name), $searchLower) ||
                       str_contains(strtolower($customer->last_name), $searchLower) ||
                       str_contains(strtolower($customer->contact_number), $searchLower) ||
                       str_contains(strtolower($customer->social_handle), $searchLower);
            })->values();
        }

        return response()->json($customers);
    }

    /**
     * Store a newly created customer.
     * Clears the customer list cache.
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

        $this->clearCustomerCache();

        return response()->json($customer, 201);
    }

    /**
     * Display a single customer.
     * Cached individually for 5 minutes.
     */
    public function show(Customer $customer)
    {
        $cacheKey = "customer_{$customer->id}";
        $ttl = now()->addMinutes(5);

        $customerData = Cache::remember($cacheKey, $ttl, function () use ($customer) {
            $customer->full_name = trim($customer->first_name . ' ' . $customer->last_name);
            return $customer;
        });

        return response()->json($customerData);
    }

    /**
     * Update an existing customer.
     * Clears relevant cache keys.
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

        $this->clearCustomerCache($customer->id);

        return response()->json($customer);
    }

    /**
     * Delete a customer.
     * Clears relevant cache keys.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        $this->clearCustomerCache($customer->id);

        return response()->json(['message' => 'Customer deleted']);
    }

    /**
     * Clear customer-related cache keys.
     * If $customerId provided, clear individual cache too.
     */
    protected function clearCustomerCache($customerId = null)
    {
        Cache::forget('customers_list');

        if ($customerId) {
            Cache::forget("customer_{$customerId}");
        }
    }
}
