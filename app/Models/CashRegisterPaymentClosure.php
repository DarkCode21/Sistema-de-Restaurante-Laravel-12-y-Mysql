<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterPaymentClosure extends Model
{
    protected $fillable = [
        'cash_register_id',
        'payment_method_id',
        'label',
        'expected_amount',
        'counted_amount',
        'difference',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'counted_amount' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
