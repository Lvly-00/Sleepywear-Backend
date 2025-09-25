<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = Customer::query();

        if ($search) {
            $query->where('first_name', 'like', "%$search%")
                ->orWhere('last_name', 'like', "%$search%")
                ->orWhere('contact_number', 'like', "%$search%");
        }

        return $query->get();
    }

    public function storeOrUpdate(Request $request)
    {
        // Search existing customer by contact number or name
        $customer = Customer::where('contact_number', $request->contact_number)
            ->orWhere(function ($q) use ($request) {
                $q->where('first_name', $request->first_name)
                    ->where('last_name', $request->last_name);
            })->first();

        if ($customer) {
            // Update customer if info changed
            $customer->update($request->only(['first_name', 'last_name', 'address', 'contact_number', 'social_handle']));
        } else {
            // Create new customer if not found
            $customer = Customer::create($request->only(['first_name', 'last_name', 'address', 'contact_number', 'social_handle']));
        }

        return $customer;
    }
}
