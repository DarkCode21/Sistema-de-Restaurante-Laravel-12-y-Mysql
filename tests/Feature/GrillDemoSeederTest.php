<?php

use App\Models\Category;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PreparationStation;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Purchase;
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

    $chickenCombo = Product::with('components')->where('name', 'Parrilla de Pollo - Pierna')->firstOrFail();
    $potatoDetail = OrderDetail::whereHas('product', fn ($query) => $query->where('name', 'Papas a elección'))
        ->whereNotNull('parent_detail_id')
        ->firstOrFail();
    $splitSale = Sale::where('customer_name', 'Pago mixto Yape y efectivo')
        ->whereHas('order', fn ($query) => $query->where('customer_phone', 'like', 'DEMO-GRILL-%'))
        ->firstOrFail();

    expect($legacyProduct->fresh()->status)->toEqual(0)
        ->and(Product::where('name', 'Parrilla de Pollo - Pecho')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Parrilla de Cerdo')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Porción de Arroz Blanco')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Papas Ancochadas')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Chicha Morada')->where('status', true)->exists())->toBeTrue()
        ->and(Product::where('name', 'Combo Parrillero Personal')->where('is_combo', true)->exists())->toBeTrue()
        ->and($chickenCombo->is_combo)->toBeTrue()
        ->and($chickenCombo->components->pluck('name')->all())->toContain('Papas a elección', 'Ensalada Criolla')
        ->and($potatoDetail->selected_options[0]['value'])->toBe('Fritas')
        ->and(PreparationStation::where('name', 'Parrilla')->exists())->toBeTrue()
        ->and(PreparationStation::where('name', 'Cocina')->exists())->toBeTrue()
        ->and(Product::where('status', true)->count())->toBeGreaterThan(15)
        ->and(Table::count())->toBeGreaterThanOrEqual(8)
        ->and(Table::where('status', 'ocupada')->count())->toBeGreaterThan(0)
        ->and(Order::where('status', 'abierto')->count())->toBeGreaterThan(0)
        ->and(OrderDetail::whereNotNull('parent_detail_id')->where('cooking_status', 'in_progress')->count())->toBeGreaterThan(0)
        ->and(Sale::whereHas('order', fn ($query) => $query->where('customer_phone', 'like', 'DEMO-GRILL-%'))->count())->toBe(24)
        ->and(SaleDetail::count())->toBeGreaterThanOrEqual(16)
        ->and(SaleDetail::whereNotNull('cost_total')->count())->toBeGreaterThan(0)
        ->and($splitSale->payments)->toHaveCount(2)
        ->and((float) $splitSale->subtotal)->toBe(51.0)
        ->and((float) $splitSale->total)->toBe(54.06)
        ->and((float) $splitSale->payments->sum('amount'))->toEqual((float) $splitSale->total)
        ->and(OrderDetail::whereNotNull('notes')->count())->toBe(2)
        ->and(OrderDetail::where('notes', 'Carne a término medio')->exists())->toBeFalse()
        ->and(Payment::count())->toBeGreaterThanOrEqual(20)
        ->and(Ingredient::count())->toBeGreaterThanOrEqual(8)
        ->and(Expense::where('concept', 'like', 'DEMO-GRILL-%')->count())->toBe(2)
        ->and(Purchase::where('reference', 'DEMO-GRILL-COMPRA-001')->exists())->toBeTrue()
        ->and(Promotion::where('name', 'DEMO-GRILL-Almuerzo 15%')->exists())->toBeTrue()
        ->and(CashRegister::where('status', 'open')->where('notes', 'DEMO-GRILL-TURNO-ACTUAL')->exists())->toBeTrue()
        ->and(CashRegister::where('status', 'closed')->whereHas('paymentClosures')->exists())->toBeTrue();
});
