<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardMetric extends Model
{
    protected $fillable = [
        'date',
        'gross_income',
        'net_income',
        'total_items_sold',
        'total_invoices',
        'collection_sales',
    ];

    protected $casts = [
        'collection_sales' => 'array',
    ];
}
