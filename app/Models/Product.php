<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use \App\Models\Concerns\HasActiveCompany;
    protected $fillable = [
        'category_id',
        'preparation_station_id',
        'name',
        'price',
        'cost',
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
        'status' => 'boolean',
        'tax_rate' => 'decimal:2',
        'cost' => 'decimal:4',
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

    public function branchStocks()
    {
        return $this->hasMany(BranchProductStock::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $product): void {
            $attributes = $product->getAttributes();
            $branchStock = new BranchProductStock([
                'stock' => $attributes['stock'] ?? 0,
                'price' => $attributes['price'] ?? null,
                'cost' => $attributes['cost'] ?? null,
                'is_available' => $attributes['status'] ?? true,
            ]);
            $branchStock->product_id = $product->id;
            $branchStock->save();
        });

        static::updated(function (self $product): void {
            if ($product->wasChanged('stock')) {
                $product->branchStocks()->updateOrCreate([], ['stock' => $product->getAttributes()['stock'] ?? 0]);
            }

            if ($product->wasChanged('status')) {
                BranchProductStock::withoutGlobalScopes()
                    ->where('product_id', $product->id)
                    ->update(['is_available' => $product->getAttributes()['status'] ?? true]);
            }
        });
    }

    public function scopeAvailableInActiveBranch(Builder $query): void
    {
        $query->where(function (Builder $available): void {
            $available->whereHas('branchStocks', fn (Builder $stock) => $stock->where('is_available', true))
                ->orWhere(function (Builder $fallback): void {
                    $fallback->whereDoesntHave('branchStocks')
                        ->where('status', true);
                });
        });
    }

    public function getPriceAttribute($value)
    {
        return $this->activeBranchStock()?->price ?? $value;
    }

    public function getCostAttribute($value)
    {
        return $this->activeBranchStock()?->cost ?? $value;
    }

    public function getStatusAttribute($value): bool
    {
        return $this->activeBranchStock()?->is_available ?? (bool) $value;
    }

    private function activeBranchStock(): ?BranchProductStock
    {
        return $this->relationLoaded('branchStocks')
            ? $this->getRelation('branchStocks')->first()
            : $this->branchStocks()->first();
    }

    public function activePromotion()
    {
        return $this->hasOne(Promotion::class)->current()->latest('id');
    }

    public function availableStock(): int
    {
        $components = $this->relationLoaded('components') ? $this->components : $this->components()->get();
        if ($this->is_combo) {
            return $components->isEmpty()
                ? 0
                : max(0, (int) $components->map(fn (self $component) => floor($component->availableStock() / max(0.0001, (float) $component->pivot->quantity)))->min());
        }

        $ingredients = $this->relationLoaded('recipeIngredients') ? $this->recipeIngredients : $this->recipeIngredients()->get();
        if ($ingredients->isNotEmpty()) {
            return max(0, (int) $ingredients->map(function (Ingredient $ingredient): int {
                $stock = $ingredient->relationLoaded('branchStocks')
                    ? $ingredient->branchStocks->first()?->stock
                    : $ingredient->branchStocks()->value('stock');

                return (int) floor((float) $stock / max(0.0001, (float) $ingredient->pivot->quantity));
            })->min());
        }

        $stock = $this->relationLoaded('branchStocks')
            ? $this->branchStocks->first()?->stock
            : $this->branchStocks()->value('stock');

        return max(0, (int) floor((float) $stock));
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
