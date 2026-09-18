<?php

use App\Models\Order;
use App\Models\OrderCorrection;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\CashRegister;
use App\Models\Setting;
use App\Models\TipAdjustment;
use App\Models\User;
use Spatie\Permission\Models\Permission;

it('shows order corrections and payments in one audit timeline', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ventas.ver'));
    $order = Order::create(['user_id' => $user->id, 'status' => 'cerrado']);
    $sale = Sale::create([
        'order_id' => $order->id,
        'cashier_id' => $user->id,
        'customer_name' => 'Cliente auditado',
        'subtotal' => 20,
        'tax' => 0,
        'tip' => 0,
        'total' => 20,
        'paid_amount' => 20,
        'change' => 0,
        'paid_at' => now(),
    ]);
    $method = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    Payment::create(['sale_id' => $sale->id, 'payment_method_id' => $method->id, 'amount' => 20, 'reference' => 'PAGO-AUDIT']);
    OrderCorrection::create([
        'order_id' => $order->id,
        'table_name' => 'Mesa auditada',
        'product_name' => 'Producto corregido',
        'quantity' => 1,
        'action' => 'cancel',
        'requires_kitchen' => false,
    ]);

    $this->actingAs($user)
        ->get(route('audits.index'))
        ->assertOk()
        ->assertSee("Pedido #{$order->id}")
        ->assertSee('Producto corregido')
        ->assertSee('PAGO-AUDIT')
        ->assertSee('Efectivo');
});

it('hides tip entries from the audit when tips are disabled', function () {
    Setting::create(['company_name' => 'Sin propinas', 'tips_enabled' => false]);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ventas.ver'));
    $cashRegister = CashRegister::create(['name' => 'Caja de auditoría', 'opening_amount' => 0, 'current_amount' => 0, 'status' => 'open', 'opened_by' => $user->id, 'opened_at' => now()]);
    $method = PaymentMethod::create(['name' => 'Yape', 'is_efectivo' => false]);
    $order = Order::create(['user_id' => $user->id, 'status' => 'cerrado']);
    $sale = Sale::create(['order_id' => $order->id, 'cash_register_id' => $cashRegister->id, 'subtotal' => 10, 'tax' => 0, 'tip' => 0, 'total' => 10, 'paid_amount' => 10, 'change' => 0, 'paid_at' => now()]);
    TipAdjustment::create(['sale_id' => $sale->id, 'cash_register_id' => $cashRegister->id, 'payment_method_id' => $method->id, 'adjusted_by' => $user->id, 'amount' => 2, 'reason' => 'Propina que no debe mostrarse', 'adjusted_at' => now()]);

    $this->actingAs($user)
        ->get(route('audits.index'))
        ->assertOk()
        ->assertDontSee('Ajustes de propina')
        ->assertDontSee('Propina que no debe mostrarse');
});
