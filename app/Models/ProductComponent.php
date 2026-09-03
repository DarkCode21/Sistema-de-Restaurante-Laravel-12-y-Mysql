<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductComponent extends Model
{
    protected $fillable = ['combo_product_id', 'component_product_id', 'quantity'];
}
