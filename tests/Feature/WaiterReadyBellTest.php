<?php

use App\Livewire\OrderCreateComponent;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

it('loads an existing open order when managing an occupied table', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $table = Table::create([
        'name' => 'Mesa con pedido',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla cargada',
        'price' => 30,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 60,
        'amount_pending' => 60,
    ]);
    $detail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'requires_kitchen' => true,
        'price' => 30,
        'subtotal' => 60,
        'cooking_status' => 'pending',
    ]);

    $component = Livewire::test(OrderCreateComponent::class, ['table' => $table])
        ->assertSet('order.id', $order->id)
        ->assertSet("cart.detail-{$detail->id}.detail_id", $detail->id)
        ->assertSet("cart.detail-{$detail->id}.quantity", 2)
        ->assertSet('cartTotal', 60.0)
        ->assertSee('Parrilla cargada');

    $component->call('increment', "detail-{$detail->id}")
        ->call('saveOrderTransaction')
        ->assertSet("cart.detail-{$detail->id}.quantity", 3)
        ->assertSet('cartTotal', 90.0);
});

it('allows the waiter to deliver ready kitchen items and drinks', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $table = Table::create([
        'name' => 'Mesa para retirar',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla lista para retirar',
        'price' => 30,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 30,
        'amount_pending' => 30,
    ]);
    $detail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 30,
        'subtotal' => 30,
        'cooking_status' => 'pending',
    ]);
    $drinkDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => false,
        'price' => 10,
        'subtotal' => 10,
        'cooking_status' => 'pending',
    ]);

    $waiter = Livewire::test(OrderCreateComponent::class, ['table' => $table]);
    $detail->update(['cooking_status' => 'ready']);

    $waiter->call('refreshReadyOrderAlert')
        ->assertSet("cart.detail-{$detail->id}.cooking_status", 'ready')
        ->assertSee('Para entregar')
        ->call('markAsServed', $detail->id)
        ->call('markAsServed', $drinkDetail->id)
        ->assertSet("cart.detail-{$detail->id}.cooking_status", 'served')
        ->assertSet("cart.detail-{$drinkDetail->id}.cooking_status", 'served');

    expect($detail->refresh()->cooking_status)->toBe('served')
        ->and($drinkDetail->refresh()->cooking_status)->toBe('served');
});

it('alerts the waiter once when every kitchen item in the order is ready', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $table = Table::create([
        'name' => 'Mesa alerta',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla de prueba',
        'price' => 30,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 60,
        'amount_pending' => 60,
    ]);
    $firstDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 30,
        'subtotal' => 30,
        'cooking_status' => 'pending',
    ]);
    $secondDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 30,
        'subtotal' => 30,
        'cooking_status' => 'pending',
    ]);

    $waiter = Livewire::test(OrderCreateComponent::class, ['table' => $table]);

    $firstDetail->update(['cooking_status' => 'ready']);
    $waiter->call('refreshReadyOrderAlert')
        ->assertNotDispatched('order-ready-for-service');

    $secondDetail->update(['cooking_status' => 'ready']);
    $waiter->call('refreshReadyOrderAlert')
        ->assertDispatched('order-ready-for-service');

    $waiter->call('refreshReadyOrderAlert')
        ->assertNotDispatched('order-ready-for-service');
});

it('uses the administrator sound setting for waiter alerts', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $table = Table::create([
        'name' => 'Mesa control de campana',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::test(OrderCreateComponent::class, ['table' => $table])
        ->assertSeeHtml('wire:poll.5s="refreshReadyOrderAlert"')
        ->assertDontSeeHtml('data-waiter-bell-toggle')
        ->assertSeeHtml('const bellEnabled = true')
        ->assertSeeHtml("Livewire.on('order-ready-for-service'");
});
