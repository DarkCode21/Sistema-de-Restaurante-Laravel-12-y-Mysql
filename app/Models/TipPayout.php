<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipPayout extends Model
{
    use \App\Models\Concerns\HasActiveBranch;

    protected $fillable = [
        'waiter_id',
        'waiter_name',
        'payment_method_id',
        'cash_register_id',
        'paid_by',
        'amount',
        'reference',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
