<?php

use App\Livewire\SalesIndexComponent;
use App\Livewire\TipReportComponent;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\TipAdjustment;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('records an audited tip adjustment without editing the original sale', function () {
    Setting::create(['company_name' => 'Restaurante de prueba', 'tips_enabled' => true]);
    $company = Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    setPermissionsTeamId($company->id);
    $manager = User::factory()->create();
    $manager->givePermissionTo(Permission::findOrCreate('ventas.ver'));
    $manager->givePermissionTo(Permission::findOrCreate('ventas.reportes'));
    $manager->givePermissionTo(Permission::findOrCreate('empresa.editar'));
    $waiter = User::factory()->create(['name' => 'Ana Mesera']);
    $manager->companies()->attach($company);
    $manager->branches()->attach($branch);
    $waiter->companies()->attach($company);
    $waiter->branches()->attach($branch);
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $cash->company_id = $company->id;
    $cash->save();
    $cashRegister = CashRegister::create([
        'name' => 'Caja de ajuste',
        'opening_amount' => 100,
        'current_amount' => 100,
        'status' => 'open',
        'opened_by' => $manager->id,
        'opened_at' => now(),
    ]);
    $cashRegister->branch_id = $branch->id;
    $cashRegister->save();

    $this->withSession(['company_id' => $company->id, 'branch_id' => $branch->id]);
    $order = Order::create(['user_id' => $waiter->id, 'status' => 'cerrado', 'total' => 22, 'amount_pending' => 0]);
    $sale = Sale::create(['order_id' => $order->id, 'cash_register_id' => $cashRegister->id, 'subtotal' => 20, 'tax' => 0, 'tip' => 2, 'total' => 22, 'paid_amount' => 22, 'change' => 0, 'paid_at' => now()]);

    Livewire::actingAs($manager)
        ->test(SalesIndexComponent::class)
        ->call('viewSale', $sale->id)
        ->call('openTipAdjustment', $sale->id)
        ->set('tipAdjustmentAmount', '3.00')
        ->set('tipAdjustmentReason', 'Cliente añadió propina después del pago.')
        ->set('tipAdjustmentPaymentMethodId', (string) $cash->id)
        ->set('tipAdjustmentCashRegisterId', (string) $cashRegister->id)
        ->call('saveTipAdjustment')
        ->assertDispatched('swal');

    expect(TipAdjustment::where('sale_id', $sale->id)->count())->toBe(1)
        ->and((float) $sale->refresh()->tip)->toBe(2.0)
        ->and($sale->adjusted_tip)->toBe(5.0)
        ->and($sale->adjusted_total)->toBe(25.0)
        ->and((float) $cashRegister->refresh()->current_amount)->toBe(103.0);

    Livewire::actingAs($manager)
        ->test(TipReportComponent::class)
        ->set('waiterId', (string) $waiter->id)
        ->assertSee('5.00');

    Livewire::actingAs($manager)
        ->test(SalesIndexComponent::class)
        ->call('openTipAdjustment', $sale->id)
        ->set('tipAdjustmentAmount', '-6.00')
        ->set('tipAdjustmentReason', 'Devolución inválida.')
        ->set('tipAdjustmentPaymentMethodId', (string) $cash->id)
        ->set('tipAdjustmentCashRegisterId', (string) $cashRegister->id)
        ->call('saveTipAdjustment');

    expect(TipAdjustment::where('sale_id', $sale->id)->count())->toBe(1)
        ->and((float) $cashRegister->refresh()->current_amount)->toBe(103.0);
});
