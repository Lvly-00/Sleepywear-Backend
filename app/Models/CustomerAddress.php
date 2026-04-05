<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'address',
        'is_default',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected $casts = [
    'is_default' => 'boolean',
];
}
