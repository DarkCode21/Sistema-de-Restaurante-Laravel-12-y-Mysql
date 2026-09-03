<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    public const UNITS = ['unit', 'g', 'kg', 'ml', 'l'];

    protected $fillable = ['name', 'unit', 'stock', 'minimum_stock'];

    protected $casts = [
        'stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')->withPivot('quantity');
    }

    public function usages()
    {
        return $this->hasMany(OrderDetailIngredient::class);
    }
}
