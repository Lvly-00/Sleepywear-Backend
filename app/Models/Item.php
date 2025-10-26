<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'code',
        'name',
        'image',
        'price',
        'status',
    ];


      protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
