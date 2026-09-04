<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'restaurant_floor_id',
        'dining_area_id',
        'name',
        'capacity',
        'shape',
        'layout_width',
        'layout_height',
        'table_width',
        'table_height',
        'orientation',
        'status',
        'x_pos',
        'y_pos'
    ];

    public function restaurantFloor()
    {
        return $this->belongsTo(RestaurantFloor::class);
    }

    public function diningArea()
    {
        return $this->belongsTo(DiningArea::class);
    }

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
