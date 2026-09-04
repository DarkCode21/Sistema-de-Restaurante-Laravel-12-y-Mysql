<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use \App\Models\Concerns\HasActiveBranch;
    protected $casts = ['paid_at' => 'datetime'];

    protected $fillable = [
        'order_id',
        'customer_name',
        'cash_register_id',
        'cashier_id',
        'subtotal',
        'tax',
        'manual_discount',
        'manual_discount_reason',
        'manual_discount_by',
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

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function details()
    {
        return $this->hasMany(SaleDetail::class);
    }

    public function manualDiscountAuthor()
    {
        return $this->belongsTo(User::class, 'manual_discount_by');
    }
}
