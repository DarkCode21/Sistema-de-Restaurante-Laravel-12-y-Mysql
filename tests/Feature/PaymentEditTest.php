<?php

use App\Livewire\SalesIndexComponent;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEdit;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('corrects a payment in an open cash register and records the change', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ordenes.cobrar'));
    $cashRegister = CashRegister::create([
        'name' => 'Caja editable',
        'opening_amount' => 100,
        'current_amount' => 120,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $yape = PaymentMethod::create(['name' => 'Yape', 'is_efectivo' => false]);
    $order = Order::create(['user_id' => $user->id, 'status' => 'cerrado']);
    $sale = Sale::create([
        'order_id' => $order->id,
        'cash_register_id' => $cashRegister->id,
        'cashier_id' => $user->id,
        'subtotal' => 20,
        'tax' => 0,
        'tip' => 0,
        'total' => 20,
        'paid_amount' => 20,
        'change' => 0,
        'paid_at' => now(),
    ]);
    $payment = Payment::create(['sale_id' => $sale->id, 'payment_method_id' => $cash->id, 'amount' => 20, 'received_amount' => 20, 'returned_amount' => 0]);

    Livewire::actingAs($user)->test(SalesIndexComponent::class)
        ->call('openPaymentEditor', $sale->id)
        ->set('paymentEdits', [[
            'id' => $payment->id,
            'payment_method_id' => (string) $yape->id,
            'amount' => '20.00',
            'reference' => 'YAPE-CORREGIDO',
        ]])
        ->set('paymentEditReason', 'Método registrado por error')
        ->call('savePaymentEdits')
        ->assertDispatched('swal');

    expect($payment->refresh()->payment_method_id)->toBe($yape->id)
        ->and($payment->reference)->toBe('YAPE-CORREGIDO')
        ->and($payment->received_amount)->toBeNull()
        ->and((float) $cashRegister->refresh()->current_amount)->toBe(100.0)
        ->and(PaymentEdit::where('payment_id', $payment->id)->value('reason'))->toBe('Método registrado por error');
});
