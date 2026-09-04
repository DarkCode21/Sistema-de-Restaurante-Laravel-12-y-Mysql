<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetailIngredient extends Model
{
    protected $fillable = ['order_detail_id', 'ingredient_id', 'quantity', 'unit_cost'];

    protected $casts = ['quantity' => 'decimal:3', 'unit_cost' => 'decimal:4'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
