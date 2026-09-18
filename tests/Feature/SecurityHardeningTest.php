<?php

use App\Livewire\OrderCreateComponent;
use App\Livewire\PaymentMethodComponent;
use App\Livewire\PreparationStationComponent;
use App\Livewire\SalesIndexComponent;
use App\Livewire\UserComponent;
use App\Models\Category;
use App\Models\Company;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('recalculates a forged cart line from the catalog', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Plato protegido',
        'price' => 20,
        'tax_rate' => 18,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);

    $screen = Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['orderType' => 'pickup'])
        ->call('addToOrder', $product->id);
    $cart = $screen->get('cart');
    $cart["new-{$product->id}-base"]['price'] = 0;
    $cart["new-{$product->id}-base"]['unit_discount'] = 20;
    $cart["new-{$product->id}-base"]['tax_rate'] = 0;
    $cart["new-{$product->id}-base"]['requires_kitchen'] = false;

    $screen->set('cart', $cart)->call('saveOrderTransaction');

    $detail = OrderDetail::firstOrFail();
    expect((float) $detail->price)->toBe(20.0)
        ->and((float) $detail->discount)->toBe(0.0)
        ->and((float) $detail->tax_rate)->toBe(18.0)
        ->and((float) $detail->subtotal)->toBe(20.0)
        ->and($detail->requires_kitchen)->toBeTrue();
});

it('does not update a user from another company', function () {
    $company = Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    $otherCompany = Company::create(['name' => 'Empresa ajena', 'slug' => 'empresa-ajena']);
    $otherBranch = $otherCompany->branches()->create(['name' => 'Sede ajena', 'code' => 'AJENA']);
    $manager = User::factory()->create();
    $victim = User::factory()->create();
    $manager->companies()->attach($company);
    $manager->branches()->attach($branch);
    $victim->companies()->attach($otherCompany);
    $victim->branches()->attach($otherBranch);

    setPermissionsTeamId($company->id);
    $manager->givePermissionTo(Permission::findOrCreate('usuarios.editar'));
    Role::firstOrCreate(['company_id' => $company->id, 'name' => 'operador', 'guard_name' => 'web']);
    $this->withSession(['company_id' => $company->id, 'branch_id' => $branch->id]);

    expect(fn () => Livewire::actingAs($manager)
        ->test(UserComponent::class)
        ->set('user_id', $victim->id)
        ->set('name', 'Cuenta alterada')
        ->set('email', 'alterada@example.com')
        ->set('password', 'clave-segura-123')
        ->set('role', 'operador')
        ->call('store'))->toThrow(ModelNotFoundException::class);

    expect($victim->refresh()->name)->not->toBe('Cuenta alterada');
});

it('does not let a read-only user mutate payment methods', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(PaymentMethodComponent::class)
        ->set('name', 'Método fraudulento')
        ->call('store')
        ->assertForbidden();
});

it('limits preparation station users to the active branch', function () {
    $company = Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    $otherCompany = Company::create(['name' => 'Empresa de cocina', 'slug' => 'empresa-de-cocina']);
    $otherBranch = $otherCompany->branches()->create(['name' => 'Cocina ajena', 'code' => 'COCINA']);
    $otherWorker = User::factory()->create(['name' => 'Cocinero ajeno']);
    $otherWorker->companies()->attach($otherCompany);
    $otherWorker->branches()->attach($otherBranch);
    $station = \App\Models\PreparationStation::create(['name' => 'Estación comprometida']);
    $station->users()->attach($otherWorker);
    $this->withSession(['company_id' => $company->id, 'branch_id' => $branch->id]);

    Livewire::test(PreparationStationComponent::class)
        ->call('edit', $station->id)
        ->assertDontSee('Cocinero ajeno')
        ->call('create')
        ->set('name', 'Parrilla')
        ->set('user_ids', [$otherWorker->id])
        ->call('store')
        ->assertHasErrors('user_ids');
});

it('limits tip adjustments to company managers', function () {
    Setting::create(['company_name' => 'Restaurante de prueba', 'tips_enabled' => true]);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(SalesIndexComponent::class)
        ->call('openTipAdjustment', 1)
        ->assertForbidden();
});
