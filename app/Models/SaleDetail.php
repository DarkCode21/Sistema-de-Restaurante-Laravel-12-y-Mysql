<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'quantity',
        'price',
        'unit_cost',
        'cost_total',
        'gross_profit',
        'discount',
        'tax',
        'tax_rate',
        'promotion_id',
        'subtotal',
        'notes',
        'selected_options',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'unit_cost' => 'decimal:4',
        'cost_total' => 'decimal:2',
        'gross_profit' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
