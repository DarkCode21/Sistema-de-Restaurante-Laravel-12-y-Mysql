<?php

use App\Livewire\OrderCreateComponent;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

it('consumes and restores the recipe snapshot instead of product stock', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $ingredient = Ingredient::create(['name' => 'Carne molida', 'unit' => 'kg', 'stock' => 1, 'minimum_stock' => 0]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Hamburguesa artesanal',
        'price' => 20,
        'stock' => 0,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $product->recipeIngredients()->attach($ingredient->id, ['quantity' => 0.250]);
    $table = Table::create([
        'name' => 'Mesa receta',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('addToOrder', $product->id)
        ->call('increment', "new-{$product->id}-base")
        ->call('saveOrderTransaction');

    $detail = OrderDetail::firstOrFail();

    expect((float) $ingredient->refresh()->branchStocks()->value('stock'))->toBe(0.5)
        ->and((float) $detail->ingredientUsages()->firstOrFail()->quantity)->toBe(0.5);

    $product->recipeIngredients()->sync([$ingredient->id => ['quantity' => 0.500]]);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table->refresh()])
        ->call('removeItem', "detail-{$detail->id}");

    expect((float) $ingredient->refresh()->branchStocks()->value('stock'))->toBe(1.0);
});

it('adds a combo when its hidden component has recipe stock', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $ingredient = Ingredient::create(['name' => 'Pollo', 'unit' => 'kg', 'stock' => 1, 'minimum_stock' => 0]);
    $component = Product::create([
        'category_id' => $category->id,
        'name' => 'Pechuga para combo',
        'price' => 0,
        'stock' => 0,
        'status' => false,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $component->recipeIngredients()->attach($ingredient->id, ['quantity' => 0.250]);
    $combo = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla de Pollo - Pecho',
        'price' => 25,
        'stock' => 0,
        'status' => true,
        'is_combo' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $combo->components()->attach($component->id, ['quantity' => 1]);
    $table = Table::create(['name' => 'Mesa combo', 'capacity' => 4, 'x_pos' => 0, 'y_pos' => 0, 'status' => 'libre']);

    expect($combo->availableStock())->toBe(4);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('addToOrder', $combo->id)
        ->assertSet("cart.new-{$combo->id}-base.quantity", 1)
        ->call('saveOrderTransaction');

    expect((float) $ingredient->refresh()->branchStocks()->value('stock'))->toBe(0.75);
});
