<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEdit extends Model
{
    use \App\Models\Concerns\HasActiveBranch;

    protected $fillable = [
        'sale_id',
        'payment_id',
        'edited_by',
        'previous_payment_method_id',
        'new_payment_method_id',
        'previous_amount',
        'new_amount',
        'previous_reference',
        'new_reference',
        'reason',
    ];

    protected $casts = [
        'previous_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function previousMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'previous_payment_method_id');
    }

    public function newMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'new_payment_method_id');
    }
}
