<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public const ORDER_TYPES = ['dine_in', 'pickup', 'delivery'];

    protected $fillable = [
        'table_id',
        'customer_id',
        'user_id',
        'order_type',
        'customer_name',
        'customer_phone',
        'delivery_address',
        'status',
        'total',
        'amount_pending'
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function corrections()
    {
        return $this->hasMany(OrderCorrection::class);
    }

    public function isReadyForCheckout(): bool
    {
        if ($this->status !== 'abierto') {
            return false;
        }

        $details = $this->relationLoaded('details')
            ? $this->details
            : $this->details()->get();

        $activeDetails = $details->where('cooking_status', '!=', 'cancelled');

        if ($activeDetails->isEmpty()) {
            return false;
        }

        return $activeDetails
            ->where('requires_kitchen', true)
            ->every(fn (OrderDetail $detail) => $detail->cooking_status === 'served');
    }

    public function getIsReadyForCheckoutAttribute(): bool
    {
        return $this->isReadyForCheckout();
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return match ($this->order_type) {
            'pickup' => 'Retiro',
            'delivery' => 'Delivery',
            default => 'Salón',
        };
    }

    public function getServiceLabelAttribute(): string
    {
        return $this->order_type === 'dine_in'
            ? 'Mesa ' . ($this->table?->name ?? 'sin mesa')
            : $this->order_type_label;
    }
}
