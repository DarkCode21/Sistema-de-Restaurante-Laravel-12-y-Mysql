<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;

it('creates a default company and branch for tenant isolation', function () {
    $company = Company::firstOrFail();
    $branch = Branch::firstOrFail();
    $user = User::factory()->create();

    $user->companies()->attach($company);
    $user->branches()->attach($branch);
    $setting = Setting::create(['company_id' => $company->id, 'company_name' => 'Configuración de prueba']);

    expect($company->slug)->toBe('restaurante-principal')
        ->and($branch->company_id)->toBe($company->id)
        ->and($branch->code)->toBe('PRINCIPAL')
        ->and($user->companies->sole()->id)->toBe($company->id)
        ->and($user->branches->sole()->id)->toBe($branch->id)
        ->and($setting->company->id)->toBe($company->id);
});
