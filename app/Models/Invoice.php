<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;


class Invoice extends Model
{
    protected $fillable = [
        'invoice_ref',
        'sent_date',
        'customer_name',
        'status',
        'total',
        'additional_fee',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_ref)) {
                $invoice->invoice_ref = 'INV-' . strtoupper(Str::random(8));
            }
        });
    }
}
