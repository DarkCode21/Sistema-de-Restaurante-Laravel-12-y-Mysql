<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionGroup extends Model
{
    protected $fillable = ['product_id', 'name', 'required'];

    protected $casts = ['required' => 'boolean'];

    public function values()
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
