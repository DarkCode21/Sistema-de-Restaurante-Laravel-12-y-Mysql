<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Table;
use Database\Seeders\GrillDemoSeeder;

it('creates a complete parrilleria demo while disabling the legacy menu', function () {
    $legacyCategory = Category::create(['name' => 'Carta anterior']);
    $legacyProduct = Product::create([
        'category_id' => $legacyCategory->id,
        'name' => 'Ceviche de prueba',
        'price' => 25,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);

    $this->seed(GrillDemoSeeder::class);

    expect($legacyProduct->fresh()->status)->toEqual(0)
        ->and(Product::where('name', 'Parrilla de Pollo - Pecho')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Parrilla de Cerdo')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Porción de Arroz Blanco')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Papas Ancochadas')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Chicha Morada')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('status', true)->count())->toBeGreaterThan(15)
        ->and(Table::count())->toBeGreaterThanOrEqual(8)
        ->and(Table::where('status', 'ocupada')->count())->toBeGreaterThan(0)
        ->and(Order::where('status', 'abierto')->count())->toBeGreaterThan(0)
        ->and(Sale::count())->toBeGreaterThanOrEqual(8)
        ->and(SaleDetail::count())->toBeGreaterThanOrEqual(16)
        ->and(Payment::count())->toBeGreaterThanOrEqual(8);
});
