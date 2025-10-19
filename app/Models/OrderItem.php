<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'item_id',
        'item_name',
        'price',
        'quantity',
        'status',
    ];
    protected $appends = ['item_code'];

    public function getItemCodeAttribute()
    {
        return $this->item?->code;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(OrderItemCustomer::class);
    }
}
