<?php

use App\Livewire\OrderCreateComponent;
use App\Livewire\ProductComponent;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;

it('shows active products before inactive products in the administration catalog', function () {
    $category = Category::create(['name' => 'Catálogo de prueba']);

    $activeProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla activa',
        'price' => 30,
        'stock' => 12,
        'status' => true,
        'image' => 'products/grill-beef.png',
        'requires_kitchen' => true,
    ]);
    $activeProduct->forceFill(['created_at' => now()->subMinute(), 'updated_at' => now()->subMinute()])->saveQuietly();

    $inactiveProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto inactivo reciente',
        'price' => 20,
        'stock' => 12,
        'status' => false,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);
    $inactiveProduct->forceFill(['created_at' => now(), 'updated_at' => now()])->saveQuietly();

    $view = app(ProductComponent::class)->render();
    $names = collect($view->getData()['products']->items())->pluck('name')->all();

    expect($names)->toBe(['Parrilla activa', 'Producto inactivo reciente']);
});

it('shows waiter categories only when they have active products', function () {
    Setting::create(['company_name' => 'Asador de prueba']);
    $table = Table::create(['name' => 'Mesa de prueba', 'capacity' => 2, 'x_pos' => 0, 'y_pos' => 0, 'status' => 'libre']);
    $activeCategory = Category::create(['name' => 'Parrillas activas']);
    $inactiveCategory = Category::create(['name' => 'Carta antigua']);

    Product::create([
        'category_id' => $activeCategory->id,
        'name' => 'Pollo activo',
        'price' => 24,
        'stock' => 10,
        'status' => true,
        'image' => 'products/grill-chicken.png',
        'requires_kitchen' => true,
    ]);
    Product::create([
        'category_id' => $inactiveCategory->id,
        'name' => 'Plato inactivo',
        'price' => 20,
        'stock' => 10,
        'status' => false,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);

    $component = app(OrderCreateComponent::class);
    $component->mount($table);
    $categoryNames = collect($component->categories)->pluck('name')->all();

    expect($categoryNames)->toContain('Parrillas activas')
        ->not->toContain('Carta antigua');
});
