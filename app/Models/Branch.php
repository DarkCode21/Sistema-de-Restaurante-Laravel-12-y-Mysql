<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['company_id', 'name', 'code', 'address', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public static function activeId(): ?int
    {
        if (app()->bound('request') && request()->hasSession()) {
            $branchId = request()->session()->get('branch_id');

            if (is_numeric($branchId)) {
                return (int) $branchId;
            }

            if (auth()->check() && !app()->environment('testing')) {
                return null;
            }
        }

        return static::query()->where('is_active', true)->value('id');
    }
}
