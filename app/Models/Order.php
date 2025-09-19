<?php

namespace App\Models;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'invoice_id',
        'first_name',
        'last_name',
        'address',
        'contact_number',
        'social_handle',
        'order_date',
        'payment_image',
        'payment_method',
        'total',
        'payment_status',
        'courier',
        'delivery_fee',
        'delivery_status'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
