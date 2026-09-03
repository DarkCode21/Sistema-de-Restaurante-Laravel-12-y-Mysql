<?php

use App\Livewire\OrderCreateComponent;
use App\Models\Category;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use Livewire\Livewire;

it('applies the active promotion and tax rate to a saved order detail', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Ceviche clásico',
        'price' => 100,
        'tax_rate' => 18,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    Promotion::create([
        'product_id' => $product->id,
        'name' => 'Happy Hour 20%',
        'discount_type' => 'percent',
        'value' => 20,
        'active' => true,
    ]);
    $table = Table::create([
        'name' => 'Mesa promo',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('addToOrder', $product->id)
        ->call('increment', "new-{$product->id}-base")
        ->call('saveOrderTransaction');

    $detail = OrderDetail::firstOrFail();

    expect((float) $detail->price)->toBe(100.0)
        ->and((float) $detail->discount)->toBe(40.0)
        ->and((float) $detail->tax_rate)->toBe(18.0)
        ->and((float) $detail->subtotal)->toBe(160.0)
        ->and((float) $detail->tax)->toBe(28.8)
        ->and($detail->promotion_id)->not->toBeNull();
});

it('ignores expired promotions when pricing an order', function () {
    Setting::create(['company_name' => 'Restaurante de prueba']);
    $user = User::factory()->create();
    $category = Category::create(['name' => 'Carta']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Lomo saltado',
        'price' => 50,
        'tax_rate' => 10,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    Promotion::create([
        'product_id' => $product->id,
        'name' => 'Promo vencida',
        'discount_type' => 'percent',
        'value' => 50,
        'active' => true,
        'ends_at' => now()->subDay(),
    ]);
    $table = Table::create([
        'name' => 'Mesa vencida',
        'capacity' => 4,
        'x_pos' => 0,
        'y_pos' => 0,
        'status' => 'libre',
    ]);

    Livewire::actingAs($user)
        ->test(OrderCreateComponent::class, ['table' => $table])
        ->call('addToOrder', $product->id)
        ->call('saveOrderTransaction');

    $detail = OrderDetail::firstOrFail();

    expect((float) $detail->discount)->toBe(0.0)
        ->and((float) $detail->subtotal)->toBe(50.0)
        ->and((float) $detail->tax)->toBe(5.0)
        ->and($detail->promotion_id)->toBeNull();
});
