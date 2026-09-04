<?php

use App\Livewire\OrdersIndexComponent;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

it('shows pickup only for ready items in the order list', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $table = Table::create([
        'name' => 'Mesa lista de retiro',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla para lista',
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
    $readyDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 30,
        'subtotal' => 30,
        'cooking_status' => 'ready',
    ]);
    $pendingDetail = OrderDetail::create([
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
        'price' => 8,
        'subtotal' => 8,
        'cooking_status' => 'pending',
    ]);

    $list = Livewire::test(OrdersIndexComponent::class)
        ->assertSee('Para entregar')
        ->assertSee('Entregar')
        ->call('markDetailAsServed', $readyDetail->id);

    expect($readyDetail->refresh()->cooking_status)->toBe('served');

    $list->call('markDetailAsServed', $pendingDetail->id)
        ->call('markDetailAsServed', $drinkDetail->id);

    expect($pendingDetail->refresh()->cooking_status)->toBe('pending')
        ->and($drinkDetail->refresh()->cooking_status)->toBe('served');

    $list->assertDontSeeHtml('aria-label="Marcar como entregado Parrilla para lista"');

    $pendingDetail->update(['cooking_status' => 'ready']);

    $list->call('refreshReadyOrderAlerts')
        ->assertDispatched('order-ready-for-service');
});
