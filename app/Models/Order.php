<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',

        'customer_id',
        'first_name',
        'last_name',
        'address',
        'contact_number',
        'social_handle',
        'order_date',
        'total',
    ];

    public function getFormattedIdAttribute()
    {
        return str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    // Relations
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Alias for orderItems (optional)
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Single latest payment
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Calculate total capital for the order
    public function totalCapital()
    {
        return $this->orderItems->sum(function ($orderItem) {
            return $orderItem->item && $orderItem->item->collection
                ? $orderItem->item->collection->capital * $orderItem->quantity
                : 0;
        });
    }
}
