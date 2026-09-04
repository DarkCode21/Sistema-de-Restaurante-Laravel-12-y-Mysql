<?php

use App\Livewire\OrderCreateComponent;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('customers can only be selected from the active company', function () {
    $company = Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    Setting::withoutGlobalScopes()->firstOrCreate(['company_id' => $company->id], ['company_name' => $company->name]);
    $otherCompany = Company::create(['name' => 'Empresa ajena', 'slug' => 'empresa-ajena']);
    $otherBranch = $otherCompany->branches()->create(['name' => 'Sede ajena', 'code' => 'AJENA']);
    $customer = User::factory()->create(['type' => 'client', 'document_number' => '12345678']);
    $customer->companies()->attach($company);
    $otherCustomer = User::factory()->create(['type' => 'client', 'document_number' => '87654321']);
    $otherCustomer->companies()->attach($otherCompany);

    $this->withSession(['company_id' => $company->id, 'branch_id' => $branch->id]);
    Livewire::test(OrderCreateComponent::class, ['orderType' => 'pickup'])
        ->call('selectCustomer', $otherCustomer->id)
        ->assertSet('customer_id', null)
        ->call('selectCustomer', $customer->id)
        ->assertSet('customer_id', $customer->id)
        ->set('newCustomer', ['name' => 'Cliente nuevo', 'document_number' => '11223344', 'phone' => '999999999'])
        ->call('saveCustomer')
        ->assertSet('customer_name', 'Cliente nuevo');

    $newCustomer = User::where('document_number', '11223344')->firstOrFail();
    expect($newCustomer->companies()->whereKey($company)->exists())->toBeTrue()
        ->and($newCustomer->companies()->whereKey($otherCompany)->exists())->toBeFalse()
        ->and($otherBranch->company_id)->toBe($otherCompany->id);
});

test('roles and permissions are isolated by company', function () {
    $company = Company::firstOrFail();
    $otherCompany = Company::create(['name' => 'Empresa roles', 'slug' => 'empresa-roles']);
    $user = User::factory()->create();
    $user->companies()->attach([$company->id, $otherCompany->id]);
    $permission = Permission::findOrCreate('empresa.editar');

    setPermissionsTeamId($company->id);
    $role = Role::create(['name' => 'encargado', 'guard_name' => 'web']);
    $role->givePermissionTo($permission);
    $user->assignRole($role);

    setPermissionsTeamId($otherCompany->id);
    $otherRole = Role::create(['name' => 'encargado', 'guard_name' => 'web']);
    $otherUser = $user->fresh()->unsetRelation('roles')->unsetRelation('permissions');

    expect($otherUser->hasRole('encargado'))->toBeFalse()
        ->and($otherUser->can('empresa.editar'))->toBeFalse();

    $otherUser->assignRole($otherRole);

    expect($otherUser->fresh()->unsetRelation('roles')->hasRole('encargado'))->toBeTrue();

    setPermissionsTeamId($company->id);
    expect($user->fresh()->unsetRelation('roles')->can('empresa.editar'))->toBeTrue();
});
