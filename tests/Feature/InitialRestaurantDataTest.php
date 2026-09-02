<?php

use App\Models\Product;
use App\Models\Table;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds tables and positive stock for every product', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Table::count())->toBe(8)
        ->and(Product::whereNull('stock')->count())->toBe(0)
        ->and(Product::where('stock', '<=', 0)->count())->toBe(0);
});

it('ships the default product image through the public storage disk', function () {
    expect(Storage::disk('public')->exists('products/default.png'))->toBeTrue()
        ->and(is_file(public_path('storage/products/default.png')))->toBeTrue();
});
