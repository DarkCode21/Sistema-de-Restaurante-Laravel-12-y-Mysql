<?php

use App\Livewire\TipReportComponent;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\TipPayout;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('filters accumulated tips by waiter', function () {
    Setting::create(['company_name' => 'Restaurante de prueba', 'tips_enabled' => true]);
    $company = \App\Models\Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    setPermissionsTeamId($company->id);

    $manager = User::factory()->create();
    $manager->givePermissionTo(Permission::firstOrCreate(['name' => 'ventas.reportes']));
    $waiter = User::factory()->create(['name' => 'Ana Mesera']);
    $otherWaiter = User::factory()->create(['name' => 'Bruno Mesero']);
    $role = Role::firstOrCreate(['name' => 'mesero', 'guard_name' => 'web', 'company_id' => $company->id]);

    foreach ([$waiter, $otherWaiter] as $user) {
        $user->companies()->attach($company);
        $user->branches()->attach($branch);
        $user->assignRole($role);
    }

    $anaOrder = Order::create(['user_id' => $waiter->id, 'status' => 'cerrado']);
    $brunoOrder = Order::create(['user_id' => $otherWaiter->id, 'status' => 'cerrado']);
    Sale::create(['order_id' => $anaOrder->id, 'subtotal' => 20, 'tax' => 0, 'tip' => 8.5, 'total' => 28.5, 'paid_amount' => 28.5, 'change' => 0, 'paid_at' => now()]);
    Sale::create(['order_id' => $brunoOrder->id, 'subtotal' => 20, 'tax' => 0, 'tip' => 3, 'total' => 23, 'paid_amount' => 23, 'change' => 0, 'paid_at' => now()]);

    $this->withSession(['company_id' => $company->id, 'branch_id' => $branch->id]);

    $report = Livewire::actingAs($manager)
        ->test(TipReportComponent::class)
        ->set('waiterSearch', 'Ana')
        ->assertSee('Ana Mesera')
        ->set('waiterId', (string) $waiter->id)
        ->assertSee('8.50')
        ->assertDontSee('Bruno Mesero')
        ->assertDontSee('3.00');

    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $yape = PaymentMethod::create(['name' => 'Yape', 'is_efectivo' => false]);
    $cash->company_id = $company->id;
    $cash->save();
    $yape->company_id = $company->id;
    $yape->save();
    $cashRegister = CashRegister::create([
        'name' => 'Caja propinas',
        'opening_amount' => 20,
        'current_amount' => 20,
        'status' => 'open',
        'opened_by' => $manager->id,
        'opened_at' => now(),
    ]);
    $cashRegister->branch_id = $branch->id;
    $cashRegister->save();

    $report
        ->call('openPayout', $waiter->id)
        ->set('payoutPaymentMethodId', (string) $cash->id)
        ->set('payoutCashRegisterId', (string) $cashRegister->id)
        ->set('payoutAmount', '5.00')
        ->call('savePayout');

    $report
        ->call('openPayout', $waiter->id)
        ->set('payoutPaymentMethodId', (string) $yape->id)
        ->set('payoutAmount', '3.00')
        ->set('payoutReference', 'Yape 999 888 777')
        ->call('savePayout');

    expect(TipPayout::where('waiter_id', $waiter->id)->count())->toBe(2)
        ->and((float) $cashRegister->refresh()->current_amount)->toBe(15.0)
        ->and(TipPayout::where('payment_method_id', $yape->id)->value('cash_register_id'))->toBeNull();
});
