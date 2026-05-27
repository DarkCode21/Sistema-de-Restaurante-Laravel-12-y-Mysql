<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'cash_register_id',
        'payment_method_id',
        'user_id',
        'concept',
        'description',
        'amount',
        'expense_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'datetime',
    ];

    /**
     * Obtener la caja chica/registradora de donde salió el gasto.
     */
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    /**
     * Obtener el método de pago utilizado.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Obtener el usuario que registró este gasto.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}