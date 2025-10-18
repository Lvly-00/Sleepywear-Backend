<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'release_date',
        'qty',
        'status',
        'total_sales',
        'stock_qty',
        'capital',

    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
