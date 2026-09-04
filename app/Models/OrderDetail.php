<?php

namespace App\Models;

use App\Models\BranchIngredientStock;
use App\Models\BranchProductStock;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $fillable = [
        'order_id',
        'parent_detail_id',
        'product_id',
        'preparation_station_id',
        'quantity',
        'requires_kitchen',
        'price',
        'discount',
        'tax',
        'tax_rate',
        'promotion_id',
        'subtotal',
        'notes',
        'selected_options',
        'cooking_status',
        'is_printed'
    ];

    protected $casts = [
        'requires_kitchen' => 'boolean',
        'is_printed' => 'boolean',
        'selected_options' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function preparationStation()
    {
        return $this->belongsTo(PreparationStation::class);
    }

    public function parentDetail()
    {
        return $this->belongsTo(self::class, 'parent_detail_id');
    }

    public function components()
    {
        return $this->hasMany(self::class, 'parent_detail_id');
    }

    public function ingredientUsages()
    {
        return $this->hasMany(OrderDetailIngredient::class);
    }

    public function consumeInventory(Product $product, int $quantity): void
    {
        $recipe = $product->recipeIngredients()->orderBy('ingredients.id')->get();

        if ($recipe->isEmpty()) {
            $stock = BranchProductStock::query()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (!$stock || (float) $stock->stock < $quantity) {
                throw new \RuntimeException("Stock insuficiente para {$product->name}");
            }

            $stock->decrement('stock', $quantity);
            return;
        }

        foreach ($recipe as $recipeIngredient) {
            $required = (float) $recipeIngredient->pivot->quantity * $quantity;
            $ingredient = BranchIngredientStock::query()
                ->where('ingredient_id', $recipeIngredient->id)
                ->lockForUpdate()
                ->first();

            if (!$ingredient || (float) $ingredient->stock < $required) {
                throw new \RuntimeException("Stock insuficiente para {$recipeIngredient->name}");
            }

            $ingredient->decrement('stock', $required);
            $this->ingredientUsages()->create([
                'ingredient_id' => $ingredient->ingredient_id,
                'quantity' => $required,
                'unit_cost' => $ingredient->unit_cost,
            ]);
        }
    }

    public function restoreInventory(): void
    {
        $usages = $this->ingredientUsages()->lockForUpdate()->get();
        $branchId = $this->order()->value('branch_id');

        if (!$branchId) {
            return;
        }

        if ($usages->isEmpty()) {
            BranchProductStock::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('product_id', $this->product_id)
                ->lockForUpdate()
                ->first()?->increment('stock', $this->quantity);
            return;
        }

        foreach ($usages as $usage) {
            BranchIngredientStock::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->where('ingredient_id', $usage->ingredient_id)
                ->lockForUpdate()
                ->first()?->increment('stock', $usage->quantity);
        }
    }

    public function getServiceStatusAttribute(): string
    {
        if ($this->cooking_status === 'served' || !$this->relationLoaded('components') || $this->components->isEmpty()) {
            return $this->cooking_status;
        }

        return $this->components
            ->where('requires_kitchen', true)
            ->every(fn (self $component) => in_array($component->cooking_status, ['ready', 'served'], true))
            ? 'ready'
            : 'in_progress';
    }
}
