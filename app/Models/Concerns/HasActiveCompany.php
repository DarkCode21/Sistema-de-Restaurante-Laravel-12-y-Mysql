<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\TenantSeedContext;
use Illuminate\Database\Eloquent\Builder;

trait HasActiveCompany
{
    public static function bootHasActiveCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query): void {
            if ($companyId = static::activeCompanyId()) {
                $query->where($query->getModel()->qualifyColumn('company_id'), $companyId);
            }
        });

        static::creating(function ($model): void {
            if (!$model->getAttribute('company_id') && ($companyId = static::activeCompanyId())) {
                $model->setAttribute('company_id', $companyId);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    private static function activeCompanyId(): ?int
    {
        if ($companyId = TenantSeedContext::companyId()) {
            return $companyId;
        }

        if (app()->bound('request') && request()->hasSession()) {
            $companyId = request()->session()->get('company_id');

            if (is_numeric($companyId)) {
                return (int) $companyId;
            }

            if (auth()->check()) {
                return null;
            }
        }

        return Company::query()->where('is_active', true)->value('id');
    }
}
