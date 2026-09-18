<?php

use App\Livewire\OrderCreateComponent;
use App\Livewire\OrdersCashierComponent;
use App\Livewire\OrdersIndexComponent;
use App\Livewire\TableComponent;
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
use Spatie\Permission\Models\Permission;

it('shows quick checkout in every waiter screen only after kitchen service is complete', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ordenes.cobrar'));
    $user->givePermissionTo(Permission::findOrCreate('ordenes.crear'));
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla entregada',
        'price' => 24,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $servedTable = Table::create([
        'name' => 'Mesa servida',
        'capacity' => 2,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $servedOrder = Order::create([
        'table_id' => $servedTable->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 24,
        'amount_pending' => 24,
    ]);
    OrderDetail::create([
        'order_id' => $servedOrder->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 24,
        'subtotal' => 24,
        'cooking_status' => 'served',
    ]);

    $readyTable = Table::create([
        'name' => 'Mesa lista',
        'capacity' => 2,
        'x_pos' => 240,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $readyOrder = Order::create([
        'table_id' => $readyTable->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 24,
        'amount_pending' => 24,
    ]);
    OrderDetail::create([
        'order_id' => $readyOrder->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 24,
        'subtotal' => 24,
        'cooking_status' => 'ready',
    ]);

    expect($servedOrder->fresh()->is_ready_for_checkout)->toBeTrue()
        ->and($readyOrder->fresh()->is_ready_for_checkout)->toBeFalse();

    Livewire::actingAs($user)->test(OrderCreateComponent::class, ['table' => $servedTable])
        ->assertSee('Cobrar mesa');

    Livewire::actingAs($user)->test(OrdersIndexComponent::class)
        ->assertSee('Cobrar mesa');

    Livewire::actingAs($user)->test(TableComponent::class)
        ->call('selectTable', $servedTable->id)
        ->assertSee('Cobrar');
});

it('requires advance checkout while a combo component remains in kitchen', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ordenes.cobrar'));
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Combo parrillero',
        'price' => 32,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $table = Table::create([
        'name' => 'Mesa con combo',
        'capacity' => 2,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 40,
        'amount_pending' => 40,
    ]);
    $combo = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => false,
        'price' => 32,
        'subtotal' => 32,
        'cooking_status' => 'pending',
    ]);
    OrderDetail::create([
        'order_id' => $order->id,
        'parent_detail_id' => $combo->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 0,
        'subtotal' => 0,
        'cooking_status' => 'in_progress',
    ]);
    OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => false,
        'price' => 8,
        'subtotal' => 8,
        'cooking_status' => 'served',
    ]);

    expect($order->fresh()->is_ready_for_checkout)->toBeFalse();

    Livewire::actingAs($user)->test(OrdersIndexComponent::class)
        ->assertSee('Cobrar adelantado')
        ->assertDontSee('Cobrar y liberar mesa');

    Livewire::actingAs($user)->withQueryParams(['order' => $order->id, 'quick_checkout' => 1])
        ->test(OrdersCashierComponent::class)
        ->assertSet('showPaymentModal', false);
});

it('starts a new order for more consumption after an advance payment', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ordenes.crear'));
    $category = Category::create(['name' => 'Bebidas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Chicha morada',
        'price' => 8,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $table = Table::create([
        'name' => 'Mesa con consumo adicional',
        'capacity' => 2,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $paidOrder = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'order_type' => 'dine_in',
        'status' => 'abierto',
        'total' => 8,
        'amount_pending' => 0,
    ]);
    OrderDetail::create([
        'order_id' => $paidOrder->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => false,
        'price' => 8,
        'subtotal' => 8,
        'cooking_status' => 'pending',
    ]);
    Sale::create([
        'order_id' => $paidOrder->id,
        'subtotal' => 8,
        'tax' => 0,
        'tip' => 0,
        'total' => 8,
        'paid_amount' => 8,
        'change' => 0,
        'paid_at' => now(),
    ]);

    Livewire::actingAs($user)->test(OrdersIndexComponent::class)
        ->assertSee('Pagado · pendiente de entrega')
        ->assertSee('Agregar consumo');

    Livewire::actingAs($user)->test(OrderCreateComponent::class, ['table' => $table])
        ->assertSet('order', null)
        ->call('addToOrder', $product->id)
        ->call('saveOrderTransaction');

    $newOrder = Order::query()
        ->where('table_id', $table->id)
        ->doesntHave('sale')
        ->sole();

    expect($newOrder->id)->not->toBe($paidOrder->id)
        ->and($newOrder->details()->count())->toBe(1)
        ->and($table->refresh()->status)->toBe('ocupada');
});

it('allows a waiter with checkout permission to open the quick payment page', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ordenes.crear'));
    $user->givePermissionTo(Permission::findOrCreate('ordenes.cobrar'));

    $this->actingAs($user)
        ->get(route('orders.cashier'))
        ->assertOk();
});

it('opens quick checkout for a served table and releases it manually after payment', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ordenes.crear'));
    $cashRegister = CashRegister::create([
        'name' => 'Caja de prueba',
        'opening_amount' => 100,
        'current_amount' => 100,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);
    $paymentMethod = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla cobrada',
        'price' => 24,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $table = Table::create([
        'name' => 'Mesa para cobro',
        'capacity' => 2,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 24,
        'amount_pending' => 24,
    ]);
    OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 24,
        'subtotal' => 24,
        'cooking_status' => 'served',
    ]);

    $decoyTable = Table::create([
        'name' => 'Mesa distractora',
        'capacity' => 2,
        'x_pos' => 240,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $decoyOrder = Order::create([
        'table_id' => $decoyTable->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 24,
        'amount_pending' => 24,
    ]);
    $decoyOrder->forceFill(['created_at' => now()->subMinute()])->save();
    OrderDetail::create([
        'order_id' => $decoyOrder->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 24,
        'subtotal' => 24,
        'cooking_status' => 'served',
    ]);

    Livewire::actingAs($user)->withQueryParams(['order' => $order->id, 'quick_checkout' => 1])
        ->test(OrdersCashierComponent::class)
        ->assertSet('showPaymentModal', true)
        ->assertSet('order.id', $order->id)
        ->assertSee('Mesa para cobro · servicio completo')
        ->assertDontSee('Mesa distractora · servicio completo')
        ->set('boxId', $cashRegister->id)
        ->set('payments', [[
            'method_id' => $paymentMethod->id,
            'amount' => 24,
            'reference' => '',
        ]])
        ->call('processPayment');

    expect($order->refresh()->status)->toBe('cerrado')
        ->and((float) $order->amount_pending)->toBe(0.0)
        ->and($table->refresh()->status)->toBe('ocupada')
        ->and(Sale::where('order_id', $order->id)->count())->toBe(1);

    Livewire::actingAs($user)->test(TableComponent::class)
        ->call('releaseTable', $table->id);

    expect($table->refresh()->status)->toBe('libre');
});
