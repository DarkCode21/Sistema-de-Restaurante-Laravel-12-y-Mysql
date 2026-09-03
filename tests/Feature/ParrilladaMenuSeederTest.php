<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Database\Seeders\ParrilladaMenuSeeder;

it('seeds the parrillada menu idempotently with default IGV', function () {
    Setting::create(['company_name' => 'Parrillada de prueba', 'default_tax_rate' => 18]);

    (new ParrilladaMenuSeeder)->run();
    $firstCount = Product::where('status', true)->count();
    expect($firstCount)->toBeGreaterThanOrEqual(40);

    $ceviche = Product::create([
        'category_id' => Category::first()->id,
        'name' => 'Ceviche Malcriado',
        'price' => 50,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);

    (new ParrilladaMenuSeeder)->run();
    expect(Product::where('status', true)->count())->toBe($firstCount + 1)
        ->and((float) Product::where('name', '¼ Pollo a la Parrilla')->first()->tax_rate)->toBe(18.0)
        ->and(Product::where('name', 'Ceviche Malcriado')->exists())->toBeTrue();
});

it('applies tax rate from settings to every product', function () {
    Setting::create(['company_name' => 'Parrillada 16%', 'default_tax_rate' => 16]);

    (new ParrilladaMenuSeeder)->run();

    expect(Product::where('tax_rate', '!=', 16)->count())->toBe(0);
});
