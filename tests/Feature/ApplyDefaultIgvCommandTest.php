<?php

use App\Console\Commands\ApplyDefaultIgvToProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

it('updates products with tax_rate = 0 to the configured default', function () {
    Setting::create(['company_name' => 'X', 'default_tax_rate' => 18]);
    $category = Category::create(['name' => 'Carta']);
    $cero = Product::create([
        'category_id' => $category->id,
        'name' => 'Ceviche sin tasa',
        'price' => 50,
        'tax_rate' => 0,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);
    $yaTasado = Product::create([
        'category_id' => $category->id,
        'name' => 'Lomo con IGV',
        'price' => 60,
        'tax_rate' => 10,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);

    $this->artisan('products:apply-default-igv', ['--apply' => true])
        ->expectsConfirmation('¿Actualizar 1 productos a tax_rate = 18%?', 'yes')
        ->assertSuccessful();

    expect((float) $cero->refresh()->tax_rate)->toBe(18.0)
        ->and((float) $yaTasado->refresh()->tax_rate)->toBe(10.0);
});

it('refuses to run when default tax rate is zero or missing', function () {
    Setting::create(['company_name' => 'X', 'default_tax_rate' => 0]);

    $this->artisan('products:apply-default-igv', ['--apply' => true])
        ->expectsOutputToContain('IGV por defecto en Settings es 0 o no existe')
        ->assertFailed();
});

it('does not run without --apply flag', function () {
    Setting::create(['company_name' => 'X', 'default_tax_rate' => 18]);
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Ceviche',
        'price' => 50,
        'tax_rate' => 0,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);

    $this->artisan('products:apply-default-igv')
        ->expectsOutputToContain('Modo seguro')
        ->assertSuccessful();

    expect((float) $product->refresh()->tax_rate)->toBe(0.0);
});
