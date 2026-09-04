<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantFloor extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function areas()
    {
        return $this->hasMany(DiningArea::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }
}
