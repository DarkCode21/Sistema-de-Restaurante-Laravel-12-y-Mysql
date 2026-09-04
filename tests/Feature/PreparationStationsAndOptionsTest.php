<?php

use App\Livewire\OrderCreateComponent;
use App\Livewire\OrdersCashierComponent;
use App\Livewire\OrdersChefComponent;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PreparationStation;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

it('records selected product options with their price and preparation station', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $station = PreparationStation::create(['name' => 'Parrilla']);
    $category = Category::create(['name' => 'Carnes']);
    $product = Product::create([
        'category_id' => $category->id,
        'preparation_station_id' => $station->id,
        'name' => 'Pollo a la parrilla',
        'price' => 30,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $group = $product->optionGroups()->create(['name' => 'Término', 'required' => true]);
    $value = $group->values()->create(['name' => 'Bien cocido', 'price_adjustment' => 3]);
    $table = Table::create([
        'name' => 'Mesa opciones',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('addToOrder', $product->id)
        ->assertSet('isOpenProductOptions', true)
        ->set("selectedOptionValueIds.{$group->id}", $value->id)
        ->call('confirmProductOptions')
        ->assertSet("cart.new-{$product->id}-{$value->id}.price", 33.0)
        ->call('saveOrderTransaction');

    $detail = OrderDetail::firstOrFail();

    expect((float) $detail->price)->toBe(33.0)
        ->and($detail->preparation_station_id)->toBe($station->id)
        ->and($detail->selected_options[0]['value'])->toBe('Bien cocido');
});

it('limits a preparation worker to their assigned station', function () {
    $grillWorker = User::factory()->create();
    $grill = PreparationStation::create(['name' => 'Parrilla']);
    $kitchen = PreparationStation::create(['name' => 'Cocina']);
    $grill->users()->attach($grillWorker);
    $category = Category::create(['name' => 'Carta']);
    $grillProduct = Product::create([
        'category_id' => $category->id,
        'preparation_station_id' => $grill->id,
        'name' => 'Bife',
        'price' => 40,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $kitchenProduct = Product::create([
        'category_id' => $category->id,
        'preparation_station_id' => $kitchen->id,
        'name' => 'Papas fritas',
        'price' => 10,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $table = Table::create([
        'name' => 'Mesa estaciones',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $grillWorker->id,
        'status' => 'abierto',
        'total' => 50,
        'amount_pending' => 50,
    ]);
    $grillDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $grillProduct->id,
        'preparation_station_id' => $grill->id,
        'quantity' => 1,
        'price' => 40,
        'subtotal' => 40,
        'requires_kitchen' => true,
        'cooking_status' => 'pending',
    ]);
    $kitchenDetail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $kitchenProduct->id,
        'preparation_station_id' => $kitchen->id,
        'quantity' => 1,
        'price' => 10,
        'subtotal' => 10,
        'requires_kitchen' => true,
        'cooking_status' => 'pending',
    ]);

    Livewire::actingAs($grillWorker)
        ->test(OrdersChefComponent::class)
        ->assertSee('Bife')
        ->assertDontSee('Papas fritas')
        ->call('markOrderAsReady', $order->id);

    expect($grillDetail->refresh()->cooking_status)->toBe('ready')
        ->and($kitchenDetail->refresh()->cooking_status)->toBe('pending');
});

it('alerts cashier when an order becomes ready for checkout', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Plato listo',
        'price' => 20,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $table = Table::create([
        'name' => 'Mesa caja',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'ocupada',
    ]);
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $user->id,
        'status' => 'abierto',
        'total' => 20,
        'amount_pending' => 20,
    ]);
    $detail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 20,
        'subtotal' => 20,
        'requires_kitchen' => true,
        'cooking_status' => 'pending',
    ]);

    $cashier = Livewire::test(OrdersCashierComponent::class)
        ->assertSeeHtml('wire:poll.5s.keep-alive="refreshReadyOrderAlerts"')
        ->assertSeeHtml('const bellEnabled = true');

    $detail->update(['cooking_status' => 'served']);

    $cashier->call('refreshReadyOrderAlerts')
        ->assertDispatched('order-ready-for-checkout');
});

it('splits a configurable combo into components for their preparation stations', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Combos']);
    $grill = PreparationStation::create(['name' => 'Parrilla']);
    $kitchen = PreparationStation::create(['name' => 'Cocina']);
    $chicken = Product::create([
        'category_id' => $category->id,
        'preparation_station_id' => $grill->id,
        'name' => 'Pollo pierna',
        'price' => 0,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $chickenGroup = $chicken->optionGroups()->create(['name' => 'Término', 'required' => true]);
    $chickenValue = $chickenGroup->values()->create(['name' => 'Bien cocido', 'price_adjustment' => 0]);
    $potato = Product::create([
        'category_id' => $category->id,
        'preparation_station_id' => $kitchen->id,
        'name' => 'Papa',
        'price' => 0,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $potatoGroup = $potato->optionGroups()->create(['name' => 'Tipo', 'required' => true]);
    $potatoValue = $potatoGroup->values()->create(['name' => 'Frita', 'price_adjustment' => 0]);
    $combo = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla de pierna',
        'price' => 35,
        'stock' => 0,
        'status' => true,
        'requires_kitchen' => false,
        'is_combo' => true,
        'image' => 'products/default.png',
    ]);
    $combo->components()->attach([
        $chicken->id => ['quantity' => 1],
        $potato->id => ['quantity' => 1],
    ]);
    $table = Table::create([
        'name' => 'Mesa combo',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('addToOrder', $combo->id)
        ->assertSet('isOpenProductOptions', true)
        ->set("selectedOptionValueIds.{$chickenGroup->id}", $chickenValue->id)
        ->set("selectedOptionValueIds.{$potatoGroup->id}", $potatoValue->id)
        ->call('confirmProductOptions')
        ->call('saveOrderTransaction');

    $parent = OrderDetail::where('product_id', $combo->id)->firstOrFail();
    $components = OrderDetail::where('parent_detail_id', $parent->id)->get()->keyBy('product_id');

    expect(OrderDetail::whereNull('parent_detail_id')->count())->toBe(1)
        ->and((float) $parent->subtotal)->toBe(35.0)
        ->and($components->get($chicken->id)->preparation_station_id)->toBe($grill->id)
        ->and($components->get($chicken->id)->selected_options[0]['value'])->toBe('Bien cocido')
        ->and($components->get($potato->id)->preparation_station_id)->toBe($kitchen->id)
        ->and($components->get($potato->id)->selected_options[0]['value'])->toBe('Frita')
        ->and($chicken->refresh()->stock)->toBe(4)
        ->and($potato->refresh()->stock)->toBe(4);

    OrderDetail::where('parent_detail_id', $parent->id)->delete();

    $screen = Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('increment', "detail-{$parent->id}");
    $additionalCombo = collect($screen->get('cart'))->first(fn ($item) => !$item['detail_id']);

    expect($components->get($potato->id)->selected_options[0]['group'])->toBe('Tipo')
        ->and($additionalCombo)->not->toBeNull()
        ->and($additionalCombo['components'])->toHaveCount(2)
        ->and($additionalCombo['quantity'])->toBe(1);
});
