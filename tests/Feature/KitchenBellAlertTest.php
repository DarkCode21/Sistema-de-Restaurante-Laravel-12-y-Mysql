<?php

use App\Livewire\OrdersChefComponent;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

function createKitchenOrder(): Order
{
    $user = User::factory()->create();
    $table = Table::create([
        'name' => 'Mesa ' . Table::query()->count() + 1,
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $category = Category::create(['name' => 'Cocina ' . Category::query()->count() + 1]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Plato de alerta ' . Product::query()->count() + 1,
        'price' => 25,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 25,
        'amount_pending' => 25,
    ]);

    OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 25,
        'subtotal' => 25,
        'cooking_status' => 'pending',
    ]);

    return $order;
}

it('alerts kitchen once for an order that arrives after the monitor opens', function () {
    $monitor = Livewire::test(OrdersChefComponent::class);

    createKitchenOrder();

    $monitor->call('refreshForAlerts')
        ->assertDispatched('kitchen-order-received');

    $monitor->call('refreshForAlerts')
        ->assertNotDispatched('kitchen-order-received');
});

it('alerts kitchen when new kitchen items are appended to an existing order', function () {
    $order = createKitchenOrder();
    $monitor = Livewire::test(OrdersChefComponent::class);
    $existingDetail = $order->details()->first();

    OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $existingDetail->product_id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 25,
        'subtotal' => 25,
        'cooking_status' => 'pending',
    ]);

    $monitor->call('refreshForAlerts')
        ->assertDispatched('kitchen-order-received');
});

it('removes a served kitchen order from the active dispatch monitor', function () {
    $order = createKitchenOrder();
    $detail = $order->details()->with('product')->first();
    $detail->update(['cooking_status' => 'served']);

    Livewire::test(OrdersChefComponent::class)
        ->assertDontSee($order->table->name)
        ->assertDontSee($detail->product->name);
});

it('renders kitchen alert controls enabled by default', function () {
    Livewire::test(OrdersChefComponent::class)
        ->assertSeeHtml('wire:poll.5s.keep-alive="refreshForAlerts"')
        ->assertSeeHtml('data-alert-bell-toggle')
        ->assertSeeHtml('aria-pressed="true"')
        ->assertSeeHtml("Livewire.on('kitchen-order-received'")
        ->assertSeeHtml('restaurant-kitchen-bell-enabled');
});

it('marks every pending kitchen item in an order as ready', function () {
    $order = createKitchenOrder();
    $firstDetail = $order->details()->firstOrFail();
    $secondDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $firstDetail->product_id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 25,
        'subtotal' => 25,
        'cooking_status' => 'in_progress',
    ]);

    Livewire::test(OrdersChefComponent::class)
        ->assertSee('Alistar todo')
        ->assertDontSee('Ver Ticket')
        ->call('markOrderAsReady', $order->id)
        ->assertDispatched('swal');

    expect($firstDetail->refresh()->cooking_status)->toBe('ready')
        ->and($secondDetail->refresh()->cooking_status)->toBe('ready');
});
