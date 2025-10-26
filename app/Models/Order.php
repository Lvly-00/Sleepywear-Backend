<?php

namespace App\Models;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'first_name',
        'last_name',
        'address',
        'contact_number',
        'social_handle',
        'order_date',
        'total',

    ];

      public function customer()
    {
        return $this->belongsTo(Customer::class);
    }


   public function invoice()
{
    return $this->hasOne(Invoice::class);
}


    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Single latest payment relation
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
