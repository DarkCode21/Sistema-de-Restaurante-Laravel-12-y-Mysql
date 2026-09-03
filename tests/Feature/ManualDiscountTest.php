<?php

use App\Livewire\OrdersCashierComponent;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

it('applies a manual discount before IGV and records its reason', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $cashRegister = CashRegister::create([
        'name' => 'Caja descuento',
        'opening_amount' => 100,
        'current_amount' => 100,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Bife descuento manual',
        'price' => 100,
        'tax_rate' => 18,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => false,
    ]);
    $table = Table::create(['name' => 'Mesa descuento', 'capacity' => 2, 'x_pos' => 0, 'y_pos' => 0, 'status' => 'ocupada']);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 118,
        'amount_pending' => 118,
    ]);
    $detail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => false,
        'price' => 100,
        'tax_rate' => 18,
        'tax' => 18,
        'subtotal' => 100,
        'cooking_status' => 'pending',
    ]);

    $cashier = Livewire::actingAs($user)
        ->test(OrdersCashierComponent::class)
        ->call('openFullPayment', $order->id)
        ->set('boxId', $cashRegister->id)
        ->set('manual_discount', 20)
        ->set('payments', [[
            'method_id' => $cash->id,
            'amount' => 94.4,
            'reference' => '',
        ]]);

    $cashier->call('processPayment');
    expect(Sale::where('order_id', $order->id)->exists())->toBeFalse();

    $cashier
        ->set('manual_discount_reason', 'Cliente frecuente')
        ->call('processPayment');

    $sale = Sale::where('order_id', $order->id)->firstOrFail();

    expect((float) $sale->manual_discount)->toBe(20.0)
        ->and($sale->manual_discount_reason)->toBe('Cliente frecuente')
        ->and($sale->manual_discount_by)->toBe($user->id)
        ->and((float) $sale->subtotal)->toBe(80.0)
        ->and((float) $sale->tax)->toBe(14.4)
        ->and((float) $sale->total)->toBe(94.4)
        ->and((float) $cashRegister->refresh()->current_amount)->toBe(194.4)
        ->and(OrderDetail::find($detail->id))->toBeNull();
});
