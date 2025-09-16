<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'image',
        'price',
        'notes',
        'collection_stock_qty',
        'collection_id'
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
