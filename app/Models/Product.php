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
        'stock',
        'status',
        'image',
        'requires_kitchen',
        'is_combo',
    ];

    protected $casts = [
        'requires_kitchen' => 'boolean',
        'is_combo' => 'boolean',
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
}
