<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Item;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    public function index()
    {
        $orders = Order::with(['items', 'payment'])
            ->orderByRaw("CASE WHEN id IN (SELECT order_id FROM payments WHERE payment_status='Unpaid') THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $orders->map(function ($order) {
            if ($order->payment && $order->payment->payment_image) {
                $order->payment_image_url = asset('storage/' . $order->payment->payment_image);
            } else {
                $order->payment_image_url = null;
            }
            return $order;
        });

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {

                // Create or update customer
                $customerData = $request->customer;
                $customer = $customerData['id']
                    ? Customer::find($customerData['id'])
                    : Customer::create($customerData);

                if ($customerData['id']) {
                    $customer->update($customerData);
                }

                // Create order
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'address' => $customer->address,
                    'contact_number' => $customer->contact_number,
                    'social_handle' => $customer->social_handle,
                    'total' => 0
                ]);

                $orderTotal = 0;

                foreach ($request->items as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $itemData['item_id'],
                        'item_name' => $itemData['item_name'],
                        'price' => $itemData['price'],
                        'quantity' => $itemData['quantity'] ?? 1,
                    ]);

                    Item::where('id', $itemData['item_id'])->update(['status' => 'Sold Out']);

                    $orderTotal += $itemData['price'] * ($itemData['quantity'] ?? 1);
                }

                $order->update(['total' => $orderTotal]);

                // Create payment record (unpaid by default)
               Payment::create([
                'order_id' => $order->id,
                'payment_status' => 'Unpaid',
                'total_paid' => 0
            ]);


                // Create invoice
                Invoice::create([
                    'order_id' => $order->id,
                    'total' => $orderTotal,
                    'status' => 'Draft',
                ]);

                return $order->load(['items', 'payment']);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Order creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Order $order)
    {
        return response()->json($order->load(['items', 'payment']));
    }

    public function update(Request $request, Order $order)
    {
        try {
            // Update customer info
            $customerData = $request->customer;
            if ($customerData) {
                $order->customer->update($customerData);
                $order->update([
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'address' => $customerData['address'],
                    'contact_number' => $customerData['contact_number'],
                    'social_handle' => $customerData['social_handle'],
                ]);
            }

            return response()->json([
                'message' => 'Order updated',
                'order' => $order->load(['items', 'payment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Order update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function updateItems(Request $request, Order $order)
    {
        try {
            return DB::transaction(function () use ($request, $order) {

                // Revert previous items to available
                foreach ($order->items as $orderItem) {
                    $item = $orderItem->item;
                    if ($item) $item->update(['status' => 'Available']);
                }

                // Delete previous order items
                $order->items()->delete();

                $orderTotal = 0;

                foreach ($request->items as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $itemData['item_id'],
                        'item_name' => $itemData['item_name'],
                        'price' => $itemData['price'],
                        'quantity' => $itemData['quantity'] ?? 1,
                    ]);

                    Item::where('id', $itemData['item_id'])->update(['status' => 'Sold Out']);

                    $orderTotal += $itemData['price'] * ($itemData['quantity'] ?? 1);
                }

                $order->update(['total' => $orderTotal]);

                // Update invoice total
                if ($order->invoice) $order->invoice->update(['total' => $orderTotal]);

                return response()->json([
                    'message' => 'Order items updated successfully',
                    'order' => $order->load(['items', 'payment'])
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update order items',
                'message' => $e->getMessage()
            ], 500);
        }
    }

//   public function updatePayment(Request $request, Payment $payment)
// {
//     try {
//         $payment->update($request->only([
//             'payment_status',
//             'payment_method',
//             'total_paid',
//             'payment_date'
//         ]));

//         if ($payment->payment_status === 'Paid' && $payment->order->invoice) {
//             $payment->order->invoice->update(['status' => 'Paid']);
//         }

//         return response()->json([
//             'message' => 'Payment updated successfully',
//             'payment' => $payment
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'error' => 'Failed to update payment',
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }


   public function destroy(Order $order)
{
    try {
        if (!$order->payment || $order->payment->payment_status !== 'Paid') {
            foreach ($order->items as $orderItem) {
                $item = $orderItem->item;
                if ($item) {
                    $item->update(['status' => 'Available']);
                }
            }
        }

        // Delete order items
        $order->items()->delete();

        // Delete payment and invoice
        if ($order->payment) $order->payment->delete();
        if ($order->invoice) $order->invoice->delete();

        // Delete order
        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    } catch (\Exception $e) {
        \Log::error('Order delete failed: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to delete order',
            'message' => $e->getMessage()
        ], 500);
    }
}

}
