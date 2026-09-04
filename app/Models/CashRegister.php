<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    use SoftDeletes;

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'cash_terminal_id',
        'opening_amount',
        'current_amount',
        'closing_amount',
        'difference',
        'status',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'notes',
        'closing_notes',
    ];

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function terminal(): BelongsTo
    {
        return $this->belongsTo(CashTerminal::class, 'cash_terminal_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cash_register_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'cash_register_id');
    }

    public function paymentClosures(): HasMany
    {
        return $this->hasMany(CashRegisterPaymentClosure::class);
    }
}
