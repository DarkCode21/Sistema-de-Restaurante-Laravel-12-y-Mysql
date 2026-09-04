<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_name', 'phone', 'document_number'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
