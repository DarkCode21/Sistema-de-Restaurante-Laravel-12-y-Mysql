<?php

use App\Livewire\OrdersCashierComponent;
use App\Livewire\PurchaseComponent;
use App\Models\Category;
use App\Models\CashRegister;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('updates ingredient stock and weighted average cost from a purchase', function () {
    $user = User::factory()->create();
    $ingredient = Ingredient::create([
        'name' => 'Carne para compra',
        'unit' => 'kg',
        'stock' => 10,
        'minimum_stock' => 1,
        'unit_cost' => 2,
    ]);
    $supplier = Supplier::create(['name' => 'Proveedor de prueba']);

    Livewire::actingAs($user)
        ->test(PurchaseComponent::class)
        ->set('supplier_id', $supplier->id)
        ->set('items', [[
            'ingredient_id' => $ingredient->id,
            'quantity' => 5,
            'unit_cost' => 4,
        ]])
        ->call('store');

    expect((float) $ingredient->refresh()->branchStocks()->value('stock'))->toBe(15.0)
        ->and((float) $ingredient->branchStocks()->value('unit_cost'))->toBe(2.6667)
        ->and($supplier->purchases()->first()->total)->toEqual('20.00');
});

it('suggests only the quantity missing below each minimum stock', function () {
    $user = User::factory()->create();
    $belowMinimum = Ingredient::create([
        'name' => 'Insumo por reponer',
        'unit' => 'kg',
        'stock' => 2,
        'minimum_stock' => 5,
        'unit_cost' => 3,
    ]);
    Ingredient::create([
        'name' => 'Insumo suficiente',
        'unit' => 'kg',
        'stock' => 5,
        'minimum_stock' => 5,
        'unit_cost' => 3,
    ]);

    Livewire::actingAs($user)
        ->test(PurchaseComponent::class)
        ->call('loadLowStockItems')
        ->assertSet('items', [[
            'ingredient_id' => $belowMinimum->id,
            'quantity' => 3.0,
            'unit_cost' => '3.00',
        ]]);
});

it('preserves recipe cost on sale and exposes it in the profit report', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('ventas.reportes'));
    $cashRegister = CashRegister::create([
        'name' => 'Turno rentable',
        'opening_amount' => 0,
        'current_amount' => 0,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);
    $card = PaymentMethod::create(['name' => 'Tarjeta', 'is_efectivo' => false]);
    $category = Category::create(['name' => 'Carta rentable']);
    $ingredient = Ingredient::create([
        'name' => 'Insumo con costo',
        'unit' => 'kg',
        'stock' => 10,
        'minimum_stock' => 1,
        'unit_cost' => 4,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Plato rentable',
        'price' => 20,
        'stock' => 0,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $product->recipeIngredients()->attach($ingredient->id, ['quantity' => 0.500]);
    $table = Table::create(['name' => 'Mesa rentable', 'capacity' => 4, 'x_pos' => 0, 'y_pos' => 0, 'status' => 'ocupada']);
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
        'requires_kitchen' => false,
        'price' => 20,
        'subtotal' => 20,
        'cooking_status' => 'pending',
    ]);
    $detail->ingredientUsages()->create(['ingredient_id' => $ingredient->id, 'quantity' => 0.500, 'unit_cost' => 4]);
    $ingredient->update(['unit_cost' => 9]);

    Livewire::actingAs($user)->test(OrdersCashierComponent::class)
        ->call('openFullPayment', $order->id)
        ->set('boxId', $cashRegister->id)
        ->set('payments', [[
            'method_id' => $card->id,
            'amount' => 20,
            'reference' => 'VISA-001',
        ]])
        ->call('processPayment');

    $saleDetail = Sale::where('order_id', $order->id)->firstOrFail()->details()->firstOrFail();

    expect((float) $saleDetail->cost_total)->toBe(2.0)
        ->and((float) $saleDetail->gross_profit)->toBe(18.0);

    $this->actingAs($user)
        ->get(route('reports.profit'))
        ->assertOk()
        ->assertViewHas('totals', fn (array $totals) => (float) $totals['gross_profit'] === 18.0)
        ->assertViewHas('products', fn ($products) => $products->perPage() === 15);

    $this->actingAs($user)
        ->get(route('reports.profit', ['category_id' => $category->id, 'product' => 'Plato rentable', 'print' => 1]))
        ->assertOk()
        ->assertViewIs('reports.profit-print')
        ->assertSee('Plato rentable');
});

it('sums the historical costs of every combo component on sale', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $cashRegister = CashRegister::create([
        'name' => 'Turno combo',
        'opening_amount' => 0,
        'current_amount' => 0,
        'status' => 'open',
        'opened_by' => $user->id,
        'opened_at' => now(),
    ]);
    $card = PaymentMethod::create(['name' => 'Tarjeta', 'is_efectivo' => false]);
    $category = Category::create(['name' => 'Carta de combos']);
    $firstIngredient = Ingredient::create(['name' => 'Insumo de combo uno', 'unit' => 'kg', 'stock' => 10, 'minimum_stock' => 1, 'unit_cost' => 4]);
    $secondIngredient = Ingredient::create(['name' => 'Insumo de combo dos', 'unit' => 'kg', 'stock' => 10, 'minimum_stock' => 1, 'unit_cost' => 3]);
    $firstComponent = Product::create(['category_id' => $category->id, 'name' => 'Componente uno', 'price' => 0, 'stock' => 0, 'status' => false, 'requires_kitchen' => false, 'image' => 'products/default.png']);
    $secondComponent = Product::create(['category_id' => $category->id, 'name' => 'Componente dos', 'price' => 0, 'stock' => 0, 'status' => false, 'requires_kitchen' => false, 'image' => 'products/default.png']);
    $firstComponent->recipeIngredients()->attach($firstIngredient->id, ['quantity' => 0.500]);
    $secondComponent->recipeIngredients()->attach($secondIngredient->id, ['quantity' => 1]);
    $combo = Product::create(['category_id' => $category->id, 'name' => 'Combo rentable', 'price' => 20, 'stock' => 0, 'status' => true, 'is_combo' => true, 'requires_kitchen' => false, 'image' => 'products/default.png']);
    $combo->components()->attach([$firstComponent->id => ['quantity' => 1], $secondComponent->id => ['quantity' => 2]]);
    $order = Order::create(['user_id' => $user->id, 'status' => 'abierto', 'total' => 40, 'amount_pending' => 40]);
    $parent = OrderDetail::create(['order_id' => $order->id, 'product_id' => $combo->id, 'quantity' => 2, 'price' => 20, 'subtotal' => 40, 'requires_kitchen' => false, 'cooking_status' => 'served']);
    $firstDetail = OrderDetail::create(['order_id' => $order->id, 'parent_detail_id' => $parent->id, 'product_id' => $firstComponent->id, 'quantity' => 2, 'price' => 0, 'subtotal' => 0, 'requires_kitchen' => false, 'cooking_status' => 'served']);
    $secondDetail = OrderDetail::create(['order_id' => $order->id, 'parent_detail_id' => $parent->id, 'product_id' => $secondComponent->id, 'quantity' => 4, 'price' => 0, 'subtotal' => 0, 'requires_kitchen' => false, 'cooking_status' => 'served']);
    $firstDetail->ingredientUsages()->create(['ingredient_id' => $firstIngredient->id, 'quantity' => 1, 'unit_cost' => 4]);
    $secondDetail->ingredientUsages()->create(['ingredient_id' => $secondIngredient->id, 'quantity' => 4, 'unit_cost' => 3]);

    Livewire::actingAs($user)->test(OrdersCashierComponent::class)
        ->call('openFullPayment', $order->id)
        ->set('boxId', $cashRegister->id)
        ->set('payments', [['method_id' => $card->id, 'amount' => 40, 'reference' => 'VISA-COMBO']])
        ->call('processPayment');

    $saleDetail = Sale::where('order_id', $order->id)->firstOrFail()->details()->firstOrFail();

    expect((float) $saleDetail->cost_total)->toBe(16.0)
        ->and((float) $saleDetail->unit_cost)->toBe(8.0)
        ->and((float) $saleDetail->gross_profit)->toBe(24.0);
});
