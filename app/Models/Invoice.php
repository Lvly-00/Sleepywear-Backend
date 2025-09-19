<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Invoice extends Model
{
    protected $fillable = [
        'invoice_ref',
        'issue_date',
        'sent_date',
        'customer_name',
        'status',
        'notes',
        'total'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
