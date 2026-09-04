<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Support\TenantSeedContext;
use Illuminate\Database\Eloquent\Builder;

trait HasActiveBranch
{
    public static function bootHasActiveBranch(): void
    {
        static::addGlobalScope('branch', function (Builder $query): void {
            if ($branchId = static::activeBranchId()) {
                $query->where($query->getModel()->qualifyColumn('branch_id'), $branchId);
            }
        });

        static::creating(function ($model): void {
            if (!$model->getAttribute('branch_id') && ($branchId = static::activeBranchId())) {
                $model->setAttribute('branch_id', $branchId);
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    private static function activeBranchId(): ?int
    {
        if ($branchId = TenantSeedContext::branchId()) {
            return $branchId;
        }

        return Branch::activeId();
    }
}
