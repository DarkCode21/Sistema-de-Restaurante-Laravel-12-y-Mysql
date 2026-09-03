<?php

use App\Http\Controllers\ReportController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Setting;

it('groups sales impact by promotion within the date range', function () {
    Setting::create(['company_name' => 'X', 'default_tax_rate' => 18]);
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
    $promo = Promotion::create([
        'product_id' => $product->id,
        'name' => 'Happy Hour',
        'discount_type' => 'percent',
        'value' => 20,
        'active' => true,
    ]);

    $sale = Sale::create([
        'subtotal' => 160,
        'tax' => 28.8,
        'tip' => 0,
        'total' => 188.8,
        'paid_amount' => 188.8,
        'change' => 0,
        'paid_at' => now(),
    ]);
    SaleDetail::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 100,
        'discount' => 40,
        'tax_rate' => 18,
        'tax' => 28.8,
        'promotion_id' => $promo->id,
        'subtotal' => 160,
    ]);

    $rows = app(ReportController::class)->promotions(request()->merge([]))->getData()['promotions'];

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['promotion']->name)->toBe('Happy Hour')
        ->and($rows->first()['times_applied'])->toBe(1)
        ->and($rows->first()['qty'])->toBe(2)
        ->and($rows->first()['total_discount'])->toBe(40.0)
        ->and($rows->first()['net_revenue'])->toBe(160.0)
        ->and($rows->first()['gross_revenue'])->toBe(188.8);
});

it('excludes promotions outside the date range', function () {
    Setting::create(['company_name' => 'X', 'default_tax_rate' => 18]);
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Ceviche',
        'price' => 50,
        'tax_rate' => 18,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);
    $promo = Promotion::create([
        'product_id' => $product->id,
        'name' => 'Promo vieja',
        'discount_type' => 'percent',
        'value' => 10,
        'active' => true,
    ]);

    $sale = Sale::create([
        'subtotal' => 90,
        'tax' => 16.2,
        'tip' => 0,
        'total' => 106.2,
        'paid_amount' => 106.2,
        'change' => 0,
        'paid_at' => now()->subMonth(),
    ]);
    SaleDetail::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'price' => 50,
        'discount' => 10,
        'tax_rate' => 18,
        'tax' => 16.2,
        'promotion_id' => $promo->id,
        'subtotal' => 90,
    ]);

    $rows = app(ReportController::class)->promotions(request()->merge([]))->getData()['promotions'];

    expect($rows)->toHaveCount(0);
});
