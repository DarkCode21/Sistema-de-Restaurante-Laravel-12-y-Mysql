<?php

use App\Models\Product;
use App\Models\Setting;
use App\Models\Table;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds the grill layout and positive stock for simple products', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Table::count())->toBe(10)
        ->and(Product::whereNull('stock')->count())->toBe(0)
        ->and(Product::where('is_combo', false)->where('stock', '<=', 0)->count())->toBe(0)
        ->and((float) Setting::firstOrFail()->default_tax_rate)->toBe(18.0);
});

it('ships product photos and the brand logo through the public storage disk', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Storage::disk('public')->exists('products/default.png'))->toBeTrue()
        ->and(Storage::disk('public')->exists('branding/parrillas-king.webp'))->toBeTrue()
        ->and(Product::where('image', 'like', '%.webp')->count())->toBe(28)
        ->and(is_file(public_path('storage/products/default.png')))->toBeTrue();
});
