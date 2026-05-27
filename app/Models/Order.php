<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'table_id',
        'customer_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'status',
        'total',
        'amount_pending'
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }
}
