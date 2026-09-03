<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCorrection extends Model
{
    protected $fillable = [
        'order_id',
        'order_detail_id',
        'preparation_station_id',
        'table_name',
        'product_name',
        'quantity',
        'action',
        'notes',
        'selected_options',
        'requires_kitchen',
        'printed_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'requires_kitchen' => 'boolean',
        'selected_options' => 'array',
        'printed_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function detail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function preparationStation()
    {
        return $this->belongsTo(PreparationStation::class);
    }

    public static function record(OrderDetail $detail, string $action): self
    {
        $order = $detail->order()->with('table')->first();

        return static::create([
            'order_id' => $detail->order_id,
            'order_detail_id' => $detail->id,
            'preparation_station_id' => $detail->preparation_station_id,
            'table_name' => $order?->getRelation('table')?->name,
            'product_name' => $detail->product?->name ?? 'Producto eliminado',
            'quantity' => $detail->quantity,
            'action' => $action,
            'notes' => $detail->notes,
            'selected_options' => $detail->selected_options,
            'requires_kitchen' => $detail->requires_kitchen,
        ]);
    }
}
