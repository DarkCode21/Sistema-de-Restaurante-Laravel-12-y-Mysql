<?php

use App\Livewire\OrderCreateComponent;
use App\Livewire\OrdersCashierComponent;
use App\Livewire\OrdersIndexComponent;
use App\Livewire\ExpenseComponent;
use App\Livewire\CashRegisterComponent;
use App\Models\CashRegister;
use App\Models\CashTerminal;
use App\Models\Category;
use App\Models\Expense;
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

function makeOperationalOrder(User $user, Product $product, string $tableName): array
{
    $table = Table::create([
        'name' => $tableName,
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => $product->price,
        'amount_pending' => $product->price,
    ]);
    $detail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => $product->requires_kitchen,
        'price' => $product->price,
        'subtotal' => $product->price,
        'cooking_status' => 'pending',
    ]);

    return [$table, $order, $detail];
}

it('restores stock when a saved item is removed', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto removible',
        'price' => 20,
        'stock' => 9,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    [$table, $order] = makeOperationalOrder($user, $product, 'Mesa de inventario');

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('removeItem', "detail-{$order->details()->first()->id}")
        ->assertSet('cart', []);

    expect($product->refresh()->stock)->toBe(10)
        ->and(Order::find($order->id))->toBeNull()
        ->and($table->refresh()->status)->toBe('libre');
});

it('rejects split-payment details from another order', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Bebidas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Bebida de prueba',
        'price' => 10,
        'stock' => 20,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    [, $firstOrder, $firstDetail] = makeOperationalOrder($user, $product, 'Mesa uno');
    [, , $secondDetail] = makeOperationalOrder($user, $product, 'Mesa dos');

    Livewire::test(OrdersCashierComponent::class)
        ->set('selectedDetails', [
            $firstOrder->id => [
                $firstDetail->id => true,
                $secondDetail->id => true,
            ],
        ])
        ->call('openSplitPayment', $firstOrder->id)
        ->assertSet('showPaymentModal', false)
        ->assertDispatched('swal');
});

it('processes card payments without opening a physical cash register', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $card = PaymentMethod::create(['name' => 'Tarjeta', 'is_efectivo' => false]);
    $category = Category::create(['name' => 'Bebidas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Bebida con tarjeta',
        'price' => 20,
        'stock' => 20,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    [, $order] = makeOperationalOrder($user, $product, 'Mesa tarjeta');

    Livewire::test(OrdersCashierComponent::class)
        ->call('openFullPayment', $order->id)
        ->set('payments', [[
            'method_id' => $card->id,
            'amount' => 20,
            'reference' => 'VISA-001',
        ]])
        ->call('processPayment');

    expect($order->refresh()->status)->toBe('cerrado')
        ->and(Sale::where('order_id', $order->id)->count())->toBe(1)
        ->and(Sale::where('order_id', $order->id)->value('cash_register_id'))->toBeNull()
        ->and(Sale::where('order_id', $order->id)->first()->payments->first()->received_amount)->toBeNull()
        ->and(Sale::where('order_id', $order->id)->first()->payments->first()->returned_amount)->toBeNull();
});

it('requires an open cash register only when receiving cash', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $category = Category::create(['name' => 'Bebidas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Bebida en efectivo',
        'price' => 20,
        'stock' => 20,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    [, $order] = makeOperationalOrder($user, $product, 'Mesa sin caja');

    Livewire::test(OrdersCashierComponent::class)
        ->call('openFullPayment', $order->id)
        ->set('payments', [[
            'method_id' => $cash->id,
            'amount' => 20,
            'reference' => '',
        ]])
        ->call('processPayment')
        ->assertDispatched('swal');

    expect(Sale::where('order_id', $order->id)->count())->toBe(0);
});

it('records a digital expense without assigning a cash register', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $yape = PaymentMethod::create(['name' => 'Yape', 'is_efectivo' => false]);

    Livewire::actingAs($user)
        ->test(ExpenseComponent::class)
        ->set('payment_method_id', $yape->id)
        ->set('concept', 'Compra de verduras')
        ->set('amount', 30)
        ->set('expense_date', now()->format('Y-m-d\TH:i'))
        ->call('store');

    expect(Expense::count())->toBe(1)
        ->and(Expense::first()->cash_register_id)->toBeNull()
        ->and((float) Expense::first()->amount)->toBe(30.0);
});

it('allows advance payment and closes the table after service', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $cashRegister = CashRegister::create([
        'name' => 'Caja de cocina',
        'opening_amount' => 100,
        'current_amount' => 100,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $category = Category::create(['name' => 'Cocina']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Plato en preparación',
        'price' => 20,
        'stock' => 9,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    [$table, $order, $detail] = makeOperationalOrder($user, $product, 'Mesa en cocina');
    $detail->update(['cooking_status' => 'in_progress', 'is_printed' => true]);

    Livewire::test(OrdersCashierComponent::class)
        ->call('openFullPayment', $order->id)
        ->assertSet('showPaymentModal', true)
        ->set('boxId', $cashRegister->id)
        ->set('payments', [[
            'method_id' => $cash->id,
            'amount' => 20,
            'reference' => '',
        ]])
        ->call('processPayment');

    expect(Sale::where('order_id', $order->id)->count())->toBe(1)
        ->and((float) $cashRegister->refresh()->current_amount)->toBe(120.0)
        ->and($order->refresh()->status)->toBe('abierto')
        ->and((float) $order->amount_pending)->toBe(0.0)
        ->and($table->refresh()->status)->toBe('ocupada')
        ->and($detail->refresh()->cooking_status)->toBe('in_progress');

    Livewire::test(OrdersIndexComponent::class)
        ->call('cancelarDetalle', $detail->id)
        ->assertDispatched('swal');

    expect($detail->refresh()->cooking_status)->toBe('in_progress');

    $detail->update(['cooking_status' => 'ready']);

    Livewire::test(OrdersIndexComponent::class)
        ->call('markDetailAsServed', $detail->id);

    expect($order->refresh()->status)->toBe('cerrado')
        ->and($table->refresh()->status)->toBe('libre');
});

it('requires a valid signature for printer endpoints', function () {
    $this->get(route('orders.kitchen-print', ['id' => 1]))->assertForbidden();
    $this->get(route('sales.print-local', ['id' => 1]))->assertForbidden();
});

it('closes an open cash register with an authorized user', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('cajas.cerrar'));
    $cashRegister = CashRegister::create([
        'name' => 'Caja por cerrar',
        'opening_amount' => 100,
        'current_amount' => 125,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);

    $this->actingAs($user)
        ->postJson(route('boxes.close', $cashRegister), ['counted_amount' => 120])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($cashRegister->refresh()->status)->toBe('closed')
        ->and($cashRegister->closed_by)->toBe($user->id)
        ->and($cashRegister->closed_at)->not->toBeNull()
        ->and((float) $cashRegister->closing_amount)->toBe(120.0)
        ->and((float) $cashRegister->difference)->toBe(-5.0);
});

it('allows only one open session per cash terminal', function () {
    $user = User::factory()->create();
    $terminal = CashTerminal::create(['name' => 'Caja secundaria', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(CashRegisterComponent::class)
        ->call('create')
        ->set('terminal_id', $terminal->id)
        ->set('opening_amount', 100)
        ->call('store');

    Livewire::actingAs($user)
        ->test(CashRegisterComponent::class)
        ->call('create')
        ->set('terminal_id', $terminal->id)
        ->set('opening_amount', 100)
        ->call('store')
        ->assertHasErrors('terminal_id');

    expect(CashRegister::where('cash_terminal_id', $terminal->id)->where('status', 'open')->count())->toBe(1);
});

it('does not allow an expense to alter a closed cash register', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $cashRegister = CashRegister::create([
        'name' => 'Caja cerrada',
        'opening_amount' => 100,
        'current_amount' => 100,
        'status' => 'closed',
        'opened_by' => $user->id,
        'opened_at' => now()->subHour(),
        'closed_by' => $user->id,
        'closed_at' => now(),
    ]);
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);

    Livewire::actingAs($user)
        ->test(ExpenseComponent::class)
        ->set('cash_register_id', $cashRegister->id)
        ->set('payment_method_id', $cash->id)
        ->set('concept', 'Compra tardía')
        ->set('amount', 10)
        ->set('expense_date', now()->format('Y-m-d\TH:i'))
        ->call('store')
        ->assertDispatched('swal');

    expect(Expense::count())->toBe(0)
        ->and((float) $cashRegister->refresh()->current_amount)->toBe(100.0);
});

it('does not allow editing a closed cash register', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $cashRegister = CashRegister::create([
        'name' => 'Caja histórica',
        'opening_amount' => 100,
        'current_amount' => 140,
        'status' => 'closed',
        'opened_by' => $user->id,
        'opened_at' => now()->subHour(),
        'closed_by' => $user->id,
        'closed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(CashRegisterComponent::class)
        ->set('cash_register_id', $cashRegister->id)
        ->set('opening_amount', 1)
        ->call('store')
        ->assertDispatched('swal');

    expect($cashRegister->refresh()->name)->toBe('Caja histórica')
        ->and((float) $cashRegister->opening_amount)->toBe(100.0);
});

it('does not allow changing the opening amount after a cash movement', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $cashRegister = CashRegister::create([
        'name' => 'Caja con gasto',
        'opening_amount' => 100,
        'current_amount' => 90,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    Expense::create([
        'cash_register_id' => $cashRegister->id,
        'payment_method_id' => $cash->id,
        'user_id' => $user->id,
        'concept' => 'Compra inicial',
        'amount' => 10,
        'expense_date' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(CashRegisterComponent::class)
        ->set('cash_register_id', $cashRegister->id)
        ->set('opening_amount', 200)
        ->call('store')
        ->assertDispatched('swal');

    expect((float) $cashRegister->refresh()->opening_amount)->toBe(100.0)
        ->and((float) $cashRegister->current_amount)->toBe(90.0);
});
