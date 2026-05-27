<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'opening_amount',
        'current_amount',
        'status',
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'notes'
    ];

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cash_register_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'cash_register_id');
    }
}
