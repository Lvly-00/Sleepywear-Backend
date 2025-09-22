<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'payment_image',
        'amount',
        'payment_status',
        'paid_at'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
