<?php

use App\Http\Controllers\DashboardController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;

it('excludes inactive legacy products from the dashboard top products ranking', function () {
    $category = Category::create(['name' => 'Prueba ranking']);
    $activeProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Parrilla Activa',
        'price' => 30,
        'stock' => 20,
        'status' => true,
        'image' => 'products/grill-beef.png',
        'requires_kitchen' => true,
    ]);
    $inactiveProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Ceviche Inactivo',
        'price' => 25,
        'stock' => 20,
        'status' => false,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);
    $sale = Sale::create([
        'subtotal' => 780,
        'tax' => 0,
        'tip' => 0,
        'total' => 780,
        'paid_amount' => 780,
        'change' => 0,
        'paid_at' => now(),
    ]);
    SaleDetail::create(['sale_id' => $sale->id, 'product_id' => $activeProduct->id, 'quantity' => 1, 'price' => 30, 'tax' => 0, 'subtotal' => 30]);
    SaleDetail::create(['sale_id' => $sale->id, 'product_id' => $inactiveProduct->id, 'quantity' => 30, 'price' => 25, 'tax' => 0, 'subtotal' => 750]);

    $rankedNames = app(DashboardController::class)
        ->topProductsForDate(today())
        ->pluck('product.name')
        ->all();

    expect($rankedNames)->toContain('Parrilla Activa')
        ->not->toContain('Ceviche Inactivo');
});
