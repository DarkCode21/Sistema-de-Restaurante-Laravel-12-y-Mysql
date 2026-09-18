<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreparationStation extends Model
{
    use \App\Models\Concerns\HasActiveBranch;
    protected $fillable = ['name', 'printer_name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
