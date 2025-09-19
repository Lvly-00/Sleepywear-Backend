<?php

namespace App\Models;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemCustomer extends Model
{
    protected $fillable = [
        'order_item_id',
        'order_id',
        'role',
        'position'
    ];
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
