<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiningArea extends Model
{
    public const TYPES = ['salon', 'terraza', 'barra', 'privado'];

    protected $fillable = ['restaurant_floor_id', 'name', 'type', 'color', 'sort_order'];

    public function restaurantFloor()
    {
        return $this->belongsTo(RestaurantFloor::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }
}
