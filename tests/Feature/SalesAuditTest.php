<?php

use App\Livewire\SalesIndexComponent;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Livewire\Livewire;

it('shows a historical pickup sale with its preserved product detail', function () {
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Nombre actual',
        'price' => 20,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => false,
    ]);
    $order = Order::create([
        'user_id' => $user->id,
        'order_type' => 'pickup',
        'customer_name' => 'Cliente histórico',
        'status' => 'cerrado',
        'total' => 20,
        'amount_pending' => 0,
    ]);
    $sale = Sale::create([
        'order_id' => $order->id,
        'customer_name' => 'Cliente histórico',
        'subtotal' => 20,
        'tax' => 0,
        'tip' => 0,
        'total' => 20,
        'paid_amount' => 20,
        'change' => 0,
        'paid_at' => now()->subDay(),
    ]);
    SaleDetail::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => 'Producto vendido',
        'quantity' => 1,
        'price' => 20,
        'tax' => 0,
        'subtotal' => 20,
    ]);

    Livewire::test(SalesIndexComponent::class)
        ->set('fromDate', now()->subDay()->format('Y-m-d'))
        ->set('toDate', now()->subDay()->format('Y-m-d'))
        ->call('viewSale', $sale->id)
        ->assertSee('Retiro')
        ->assertSee('Producto vendido')
        ->assertSee('Cliente histórico');
});

it('shows payment totals for the selected date range', function () {
    $user = User::factory()->create();
    $cash = PaymentMethod::create(['name' => 'Efectivo', 'is_efectivo' => true]);
    $yape = PaymentMethod::create(['name' => 'Yape', 'is_efectivo' => false]);
    $firstOrder = Order::create(['user_id' => $user->id, 'status' => 'cerrado']);
    $secondOrder = Order::create(['user_id' => $user->id, 'status' => 'cerrado']);
    $firstSale = Sale::create(['order_id' => $firstOrder->id, 'subtotal' => 20, 'tax' => 0, 'tip' => 0, 'total' => 20, 'paid_amount' => 20, 'change' => 0, 'paid_at' => now()]);
    $secondSale = Sale::create(['order_id' => $secondOrder->id, 'subtotal' => 15, 'tax' => 0, 'tip' => 0, 'total' => 15, 'paid_amount' => 15, 'change' => 0, 'paid_at' => now()]);
    Payment::create(['sale_id' => $firstSale->id, 'payment_method_id' => $cash->id, 'amount' => 20]);
    Payment::create(['sale_id' => $secondSale->id, 'payment_method_id' => $yape->id, 'amount' => 15]);

    Livewire::test(SalesIndexComponent::class)
        ->set('fromDate', now()->format('Y-m-d'))
        ->set('toDate', now()->format('Y-m-d'))
        ->assertSee('Efectivo')
        ->assertSee('Yape')
        ->assertDontSee('Tarjeta')
        ->assertSee('20.00')
        ->assertSee('15.00');
});
