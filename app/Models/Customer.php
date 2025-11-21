<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'user_id',

        'first_name',
        'last_name',
        'address',
        'contact_number',
        'social_handle',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
