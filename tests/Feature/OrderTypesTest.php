<?php

use App\Livewire\OrderCreateComponent;
use App\Livewire\OrdersCashierComponent;
use App\Livewire\OrdersChefComponent;
use App\Livewire\OrdersIndexComponent;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

it('requires contact data for delivery and saves it without a table', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Hamburguesa',
        'price' => 20,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);

    $component = Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => null, 'orderType' => 'delivery'])
        ->call('addToOrder', $product->id)
        ->call('saveOrderTransaction');

    expect(Order::count())->toBe(0);

    $component
        ->set('customer_name', 'Ana Pérez')
        ->set('customer_phone', '999123456')
        ->set('delivery_address', 'Av. Central 123, puerta negra')
        ->call('saveOrderTransaction');

    $order = Order::firstOrFail();

    expect($order->order_type)->toBe('delivery')
        ->and($order->table_id)->toBeNull()
        ->and($order->customer_name)->toBe('Ana Pérez')
        ->and($order->customer_phone)->toBe('999123456')
        ->and($order->delivery_address)->toBe('Av. Central 123, puerta negra');
});

it('shows delivery orders in the waiter, kitchen, and cashier queues', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Pizza delivery',
        'price' => 30,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $order = Order::create([
        'user_id' => $user->id,
        'order_type' => 'delivery',
        'customer_name' => 'Ana Pérez',
        'customer_phone' => '999123456',
        'delivery_address' => 'Av. Central 123',
        'status' => 'abierto',
        'total' => 30,
        'amount_pending' => 30,
    ]);
    OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 30,
        'subtotal' => 30,
        'requires_kitchen' => true,
        'cooking_status' => 'pending',
    ]);

    Livewire::actingAs($user)->test(OrdersIndexComponent::class)->assertSee('Delivery');
    Livewire::actingAs($user)->test(OrdersChefComponent::class)->assertSee('Delivery')->assertSee('Pizza delivery');
    Livewire::actingAs($user)->test(OrdersCashierComponent::class)->assertSee('Delivery');
});

it('shows the assigned dining table instead of a missing-table label', function () {
    $user = User::factory()->create();
    $table = Table::create([
        'name' => 'Mesa 12',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'order_type' => 'dine_in',
        'status' => 'abierto',
    ]);

    expect($order->fresh()->service_label)->toBe('Mesa 12');
});
