<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'preparation_station_id',
        'name',
        'price',
        'tax_rate',
        'stock',
        'status',
        'image',
        'requires_kitchen',
        'is_combo',
    ];

    protected $casts = [
        'requires_kitchen' => 'boolean',
        'is_combo' => 'boolean',
        'tax_rate' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function preparationStation()
    {
        return $this->belongsTo(PreparationStation::class);
    }

    public function optionGroups()
    {
        return $this->hasMany(ProductOptionGroup::class);
    }

    public function components()
    {
        return $this->belongsToMany(self::class, 'product_components', 'combo_product_id', 'component_product_id')
            ->withPivot('quantity');
    }

    public function recipeIngredients()
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')->withPivot('quantity');
    }

    public function promotions()
    {
        return $this->hasMany(Promotion::class);
    }

    public function activePromotion()
    {
        return $this->hasOne(Promotion::class)->current()->latest('id');
    }

    public function unitBreakdown(int $quantity, float $priceAdjustment = 0, ?Promotion $promotion = null): array
    {
        $unitPrice = round((float) $this->price + $priceAdjustment, 2);
        $unitDiscount = 0.0;
        $promotionId = null;

        $promotion ??= $this->relationLoaded('activePromotion') ? $this->activePromotion : null;

        if ($promotion) {
            $promotionId = $promotion->id;
            $unitDiscount = $promotion->discount_type === 'percent'
                ? round($unitPrice * (float) $promotion->value / 100, 2)
                : min((float) $promotion->value, $unitPrice);
        }

        $lineSubtotal = round(($unitPrice - $unitDiscount) * $quantity, 2);
        $taxRate = (float) $this->tax_rate;
        $tax = round($lineSubtotal * $taxRate / 100, 2);

        return [
            'price' => $unitPrice,
            'discount' => round($unitDiscount * $quantity, 2),
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'subtotal' => $lineSubtotal,
            'promotion_id' => $promotionId,
        ];
    }
}
