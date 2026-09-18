<?php

use App\Livewire\TenantComponent;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function tenantManager(): User
{
    $user = User::factory()->create();
    $company = Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    $user->companies()->attach($company);
    $user->branches()->attach($branch);
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'empresa.editar']));

    return $user;
}

it('creates a company with its initial branch and setting', function () {
    $user = tenantManager();

    Livewire::actingAs($user)
        ->test(TenantComponent::class)
        ->set('company_name', 'Cevichería Norte')
        ->set('initial_branch_name', 'Sede Miraflores')
        ->set('initial_branch_code', 'MIR')
        ->call('createCompany');

    $company = Company::where('slug', 'cevicheria-norte')->firstOrFail();
    $branch = Branch::where('company_id', $company->id)->where('code', 'MIR')->firstOrFail();

    expect($user->fresh()->companies()->whereKey($company)->exists())->toBeTrue()
        ->and($user->fresh()->branches()->whereKey($branch)->exists())->toBeTrue()
        ->and(Setting::withoutGlobalScopes()->where('company_id', $company->id)->value('company_name'))->toBe('Cevichería Norte');
});

it('adds a branch only to a company assigned to the manager', function () {
    $user = tenantManager();
    $company = $user->companies()->firstOrFail();

    Livewire::actingAs($user)
        ->test(TenantComponent::class)
        ->set('branch_company_id', $company->id)
        ->set('branch_name', 'Sede Centro')
        ->set('branch_code', 'CTR')
        ->call('createBranch');

    $branch = Branch::where('company_id', $company->id)->where('code', 'CTR')->firstOrFail();

    expect($user->fresh()->branches()->whereKey($branch)->exists())->toBeTrue();
});

it('saves settings for the selected company', function () {
    $user = tenantManager();
    $company = Company::create(['name' => 'Cevichería Sur', 'slug' => 'cevicheria-sur']);
    $branch = $company->branches()->create(['name' => 'Sede Sur', 'code' => 'SUR']);
    Setting::withoutGlobalScopes()->create(['company_id' => $company->id, 'company_name' => 'Anterior']);
    $user->companies()->attach($company);
    $user->branches()->attach($branch);
    setPermissionsTeamId($company->id);
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'empresa.editar']));

    $this->actingAs($user)
        ->withSession(['company_id' => $company->id, 'branch_id' => $branch->id])
        ->put(route('settings.update'), [
            'company_name' => 'Cevichería Sur',
            'company_email' => 'sur@example.test',
            'company_phone' => '999999999',
            'company_address' => 'Av. Sur 123',
            'currency_simbol' => 'S/',
            'default_tax_rate' => 18,
            'timezone' => 'America/Lima',
            'tips_enabled' => 0,
        ])
        ->assertSessionHasNoErrors();

    expect(Setting::withoutGlobalScopes()->where('company_id', $company->id)->value('company_name'))->toBe('Cevichería Sur')
        ->and((bool) Setting::withoutGlobalScopes()->where('company_id', $company->id)->value('tips_enabled'))->toBeFalse();
});
