<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'additional_fee',
        'payment_status',
        'payment_method',
        'total',
        'payment_date',

    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'total_paid' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
