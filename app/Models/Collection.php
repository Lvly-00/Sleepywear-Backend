<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'release_date',
        'qty',
        'capital',
        'total_sales',
        'stock_qty',
        'status',

    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
