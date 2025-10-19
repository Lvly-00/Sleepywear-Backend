<?php

namespace App\Models;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'first_name',
        'last_name',
        'address',
        'contact_number',
        'social_handle',
        'order_date',
        'total',
        'payment_status',
        'payment_method',
        'payment_image',
        'total_paid',
        'payment_date',

    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->items();
    }

    // Single latest payment relation
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
