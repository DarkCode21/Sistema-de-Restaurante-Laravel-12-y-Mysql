<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'order_id',
        'cash_register_id',
        'subtotal',
        'tax',
        'tip',
        'total',
        'paid_amount',
        'change',
        'paid_at'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }
}
