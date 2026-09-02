<?php

use App\Livewire\OrderCreateComponent;
use App\Livewire\OrdersChefComponent;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderCorrection;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

function makeKitchenCorrectionOrder(string $status = 'in_progress'): array
{
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Parrillas']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Plato de corrección',
        'price' => 25,
        'stock' => 9,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $table = Table::create([
        'name' => 'Mesa corrección',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 25,
        'amount_pending' => 25,
    ]);
    $detail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 25,
        'subtotal' => 25,
        'cooking_status' => $status,
        'is_printed' => $status !== 'pending',
    ]);

    return [$user, $table, $order, $detail, $product];
}

it('keeps duplicate products as separate saved order details', function () {
    [$user, $table, $order, $detail] = makeKitchenCorrectionOrder('pending');
    $secondDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $detail->product_id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 25,
        'subtotal' => 25,
        'notes' => 'Sin cebolla',
        'cooking_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->assertSet("cart.detail-{$detail->id}.detail_id", $detail->id)
        ->assertSet("cart.detail-{$secondDetail->id}.detail_id", $secondDetail->id);
});

it('clears a stale cart when its order is closed elsewhere', function () {
    [$user, $table, $order] = makeKitchenCorrectionOrder();

    $waiter = Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table]);

    $order->update(['status' => 'cerrado']);

    $waiter->call('refreshReadyOrderAlert')
        ->assertSet('order', null)
        ->assertSet('cart', []);
});

it('sends a correction instead of deleting an item already sent to kitchen', function () {
    [$user, $table, $order, $detail, $product] = makeKitchenCorrectionOrder();
    $remainingDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 25,
        'subtotal' => 25,
        'cooking_status' => 'pending',
    ]);

    $firstWaiter = Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table]);
    $staleWaiter = Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table]);

    $firstWaiter
        ->call('removeItem', "detail-{$detail->id}")
        ->assertDispatched('auto-print-kitchen-correction')
        ->assertSet('cart', fn (array $cart) => array_keys($cart) === ["detail-{$remainingDetail->id}"]);

    $staleWaiter
        ->call('removeItem', "detail-{$detail->id}")
        ->assertDispatched('swal');

    expect($detail->refresh()->cooking_status)->toBe('cancelled')
        ->and($order->refresh()->status)->toBe('abierto')
        ->and($table->refresh()->status)->toBe('ocupada')
        ->and((int) $product->refresh()->stock)->toBe(10)
        ->and(OrderCorrection::where('order_id', $order->id)->value('action'))->toBe('cancel')
        ->and(OrderCorrection::where('order_id', $order->id)->count())->toBe(1);
});

it('adds a new pending line instead of changing sent kitchen quantity', function () {
    [$user, $table, $order, $detail, $product] = makeKitchenCorrectionOrder();

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('increment', "detail-{$detail->id}")
        ->call('saveOrderTransaction')
        ->assertDispatched('auto-print-kitchen');

    $newDetail = $order->details()
        ->whereNotIn('id', [$detail->id])
        ->firstOrFail();

    expect($detail->refresh()->quantity)->toBe(1)
        ->and($detail->cooking_status)->toBe('in_progress')
        ->and($newDetail->quantity)->toBe(1)
        ->and($newDetail->cooking_status)->toBe('pending')
        ->and($newDetail->is_printed)->toBeFalse()
        ->and((int) $product->refresh()->stock)->toBe(8);
});

it('keeps the correction snapshot after a detail changes again', function () {
    [$user, $table, , $detail] = makeKitchenCorrectionOrder();

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('updateNote', $detail->id, 'Sin cebolla')
        ->assertDispatched('auto-print-kitchen-correction');

    $table->update(['name' => 'Mesa renombrada']);
    $detail->update(['notes' => 'Con queso', 'cooking_status' => 'cancelled']);
    $correction = OrderCorrection::firstOrFail();

    expect($correction->action)->toBe('update')
        ->and($correction->notes)->toBe('Sin cebolla')
        ->and($correction->quantity)->toBe(1)
        ->and($correction->table_name)->toBe('Mesa corrección');
});

it('does not alter a served item or its stock', function () {
    [$user, $table, , $detail, $product] = makeKitchenCorrectionOrder('served');

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('removeItem', "detail-{$detail->id}")
        ->assertDispatched('swal');

    expect($detail->refresh()->cooking_status)->toBe('served')
        ->and((int) $product->refresh()->stock)->toBe(9);
});

it('keeps a kitchen correction visible until kitchen confirms it', function () {
    [, , , $detail] = makeKitchenCorrectionOrder();
    $correction = OrderCorrection::record($detail, 'update');

    Livewire::test(OrdersChefComponent::class)
        ->assertSee('Plato de corrección')
        ->assertSee('Actualizar a 1')
        ->call('acknowledgeCorrection', $correction->id)
        ->assertDispatched('swal');

    expect($correction->refresh()->acknowledged_at)->not->toBeNull();
});

it('requires kitchen corrections for one item to be confirmed in order', function () {
    [, , , $detail] = makeKitchenCorrectionOrder();
    $firstCorrection = OrderCorrection::record($detail, 'update');
    $detail->update(['notes' => 'Sin cebolla']);
    $secondCorrection = OrderCorrection::record($detail, 'update');

    $chef = Livewire::test(OrdersChefComponent::class);
    $chef->call('acknowledgeCorrection', $secondCorrection->id)
        ->assertDispatched('swal');

    expect($secondCorrection->refresh()->acknowledged_at)->toBeNull();

    $chef->call('acknowledgeCorrection', $firstCorrection->id)
        ->call('acknowledgeCorrection', $secondCorrection->id);

    expect($firstCorrection->refresh()->acknowledged_at)->not->toBeNull()
        ->and($secondCorrection->refresh()->acknowledged_at)->not->toBeNull();
});
