<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Setting;

it('sums todays tax and discounts for the dashboard cards', function () {
    Setting::create(['company_name' => 'Restaurante de prueba', 'default_tax_rate' => 18]);
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Ceviche',
        'price' => 100,
        'tax_rate' => 18,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);

    $todaySale = Sale::create([
        'subtotal' => 200,
        'tax' => 36,
        'tip' => 0,
        'total' => 236,
        'paid_amount' => 236,
        'change' => 0,
        'paid_at' => now(),
    ]);
    SaleDetail::create([
        'sale_id' => $todaySale->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100,
        'discount' => 20,
        'tax_rate' => 18,
        'tax' => 32.4,
        'subtotal' => 180,
    ]);

    $yesterdaySale = Sale::create([
        'subtotal' => 50,
        'tax' => 9,
        'tip' => 0,
        'total' => 59,
        'paid_amount' => 59,
        'change' => 0,
        'paid_at' => now()->subDay(),
    ]);
    SaleDetail::create([
        'sale_id' => $yesterdaySale->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 50,
        'discount' => 5,
        'tax_rate' => 18,
        'tax' => 8.1,
        'subtotal' => 45,
    ]);

    expect((float) Sale::whereDate('paid_at', today())->sum('tax'))->toBe(36.0)
        ->and((float) SaleDetail::whereHas('sale', fn ($q) => $q->whereDate('paid_at', today()))->sum('discount'))->toBe(20.0)
        ->and((float) Sale::whereDate('paid_at', today()->subDay())->sum('tax'))->toBe(9.0)
        ->and((float) SaleDetail::whereHas('sale', fn ($q) => $q->whereDate('paid_at', today()->subDay()))->sum('discount'))->toBe(5.0);
});
