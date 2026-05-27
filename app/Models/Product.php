<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'price',
        'stock',
        'status',
        'image',
        'requires_kitchen'
    ];

    protected $casts = [
        'requires_kitchen' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
