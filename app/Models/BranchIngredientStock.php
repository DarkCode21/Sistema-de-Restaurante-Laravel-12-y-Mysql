<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchIngredientStock extends Model
{
    use \App\Models\Concerns\HasActiveBranch;

    protected $fillable = ['ingredient_id', 'stock', 'minimum_stock', 'unit_cost'];

    protected $casts = [
        'stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'unit_cost' => 'decimal:4',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
