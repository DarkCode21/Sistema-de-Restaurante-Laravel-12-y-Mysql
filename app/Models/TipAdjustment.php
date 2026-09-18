<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipAdjustment extends Model
{
    use \App\Models\Concerns\HasActiveBranch;

    protected $fillable = [
        'sale_id',
        'cash_register_id',
        'payment_method_id',
        'adjusted_by',
        'amount',
        'reason',
        'reference',
        'adjusted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'adjusted_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
