<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Database\Seeders\ParrilladaMenuSeeder;

it('re-seeds the menu without duplicating products', function () {
    Setting::create(['company_name' => 'Parrillada', 'default_tax_rate' => 18]);
    $this->artisan('menu:seed')->assertSuccessful();

    $count = Product::where('status', true)->count();

    $this->artisan('menu:seed')->assertSuccessful();

    expect(Product::where('status', true)->count())->toBe($count);
});

it('deactivates products outside the parrillada menu when --fresh is passed', function () {
    Setting::create(['company_name' => 'Parrillada', 'default_tax_rate' => 18]);
    $category = Category::create(['name' => 'Carta anterior']);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Lomo Saltado Malcriado',
        'price' => 40,
        'tax_rate' => 0,
        'stock' => 10,
        'status' => true,
        'image' => 'products/default.png',
        'requires_kitchen' => true,
    ]);

    $this->artisan('menu:seed', ['--fresh' => true])
        ->expectsConfirmation('Hay 1 categorías que no son del menú parrillero. ¿Eliminarlas?', 'no')
        ->assertSuccessful();

    expect(Product::where('name', 'Lomo Saltado Malcriado')->first()->status)->toEqual(0)
        ->and(Product::where('name', '¼ Pollo a la Parrilla')->where('status', true)->exists())->toBeTrue();
});
