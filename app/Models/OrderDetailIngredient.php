<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetailIngredient extends Model
{
    protected $fillable = ['order_detail_id', 'ingredient_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
