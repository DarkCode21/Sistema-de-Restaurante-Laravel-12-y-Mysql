<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'status',
        'x_pos',
        'y_pos'
    ];

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'libre' => 'emerald',
            'ocupada' => 'rose',
            'reservada' => 'amber',
            default => 'slate',
        };
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
