<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionSalesSummary extends Model
{
    protected $table = 'collection_sales_summary';

    protected $fillable = [
        'user_id',

        'collection_id',
        'collection_name',
        'collection_capital',
        'date',
        'total_sales',
        'total_items_sold',
        'total_customers',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }
}
