<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use SoftDeletes;
    use \App\Models\Concerns\HasActiveCompany;

    protected $fillable = ['name', 'is_efectivo'];

    protected $casts = [
        'is_efectivo' => 'boolean',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tipPayouts(): HasMany
    {
        return $this->hasMany(TipPayout::class);
    }

    public function tipAdjustments(): HasMany
    {
        return $this->hasMany(TipAdjustment::class);
    }
}
