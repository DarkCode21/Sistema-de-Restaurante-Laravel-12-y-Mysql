<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchProductStock extends Model
{
    use \App\Models\Concerns\HasActiveBranch;

    protected $fillable = ['product_id', 'stock', 'price', 'cost', 'is_available'];

    protected $casts = [
        'stock' => 'integer',
        'price' => 'decimal:2',
        'cost' => 'decimal:4',
        'is_available' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
