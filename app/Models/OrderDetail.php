<?php

namespace App\Models;

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

    public function restoreInventory(): void
    {
        $usages = $this->ingredientUsages()->lockForUpdate()->get();

        if ($usages->isEmpty()) {
            Product::query()->whereKey($this->product_id)->lockForUpdate()->first()?->increment('stock', $this->quantity);
            return;
        }

        foreach ($usages as $usage) {
            Ingredient::query()->whereKey($usage->ingredient_id)->lockForUpdate()->first()?->increment('stock', $usage->quantity);
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
