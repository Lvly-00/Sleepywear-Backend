<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_status',
        'payment_method',
        'total_paid',
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
