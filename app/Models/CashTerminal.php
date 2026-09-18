<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTerminal extends Model
{
    use \App\Models\Concerns\HasActiveBranch;
    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function sessions()
    {
        return $this->hasMany(CashRegister::class);
    }
}
