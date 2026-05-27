<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'sale_id',
        'payment_method_id',
        'amount',
        'received_amount',
        'returned_amount',
        'reference'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
