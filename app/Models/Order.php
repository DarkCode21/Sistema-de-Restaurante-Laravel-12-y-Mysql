<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'table_id',
        'customer_id',
        'user_id',
        'customer_name',
        'customer_phone',
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
}
