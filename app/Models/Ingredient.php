<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use \App\Models\Concerns\HasActiveCompany;
    public const UNITS = ['unit', 'g', 'kg', 'ml', 'l'];

    protected $fillable = ['name', 'unit', 'stock', 'minimum_stock', 'unit_cost'];

    protected $casts = [
        'stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'unit_cost' => 'decimal:4',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')->withPivot('quantity');
    }

    public function usages()
    {
        return $this->hasMany(OrderDetailIngredient::class);
    }

    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    public function branchStocks()
    {
        return $this->hasMany(BranchIngredientStock::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $ingredient): void {
            $ingredient->branchStocks()->firstOrCreate([], [
                'stock' => $ingredient->stock ?? 0,
                'minimum_stock' => $ingredient->minimum_stock ?? 0,
                'unit_cost' => $ingredient->unit_cost,
            ]);
        });
    }
}
