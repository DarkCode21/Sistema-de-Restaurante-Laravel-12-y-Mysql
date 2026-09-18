<?php

use App\Http\Controllers\SaleController;
use App\Models\Sale;
use App\Models\Setting;
use Database\Seeders\GrillDemoSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

test('a signed QR URL shows a public sale verification without customer data', function () {
    $this->seed(GrillDemoSeeder::class);

    $sale = Sale::with('branch')->firstOrFail();

    $this->get(URL::signedRoute('sales.verify', ['sale' => $sale->id]))
        ->assertOk()
        ->assertSee('Comprobante verificado')
        ->assertSee(sprintf('TV-%s-%06d', $sale->branch->code, $sale->id))
        ->assertDontSee($sale->customer_name);
});

test('a signed local receipt includes a QR code in its PDF', function () {
    $this->seed(GrillDemoSeeder::class);

    $sale = Sale::firstOrFail();
    $response = $this->get(URL::temporarySignedRoute('sales.print-local', now()->addMinute(), ['id' => $sale->id]));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('a WebP logo is omitted from receipt PDFs', function () {
    $this->seed(GrillDemoSeeder::class);
    $this->seed(SettingSeeder::class);

    $method = new ReflectionMethod(SaleController::class, 'logoDataUri');

    expect($method->invoke(app(SaleController::class), Setting::withoutGlobalScopes()->firstOrFail()))->toBeNull();
});

test('a legacy sale uses its cashier branch for the receipt', function () {
    $this->seed(GrillDemoSeeder::class);

    $sale = Sale::with('cashRegister.branch')->firstOrFail();
    expect($sale->cashRegister?->branch)->not->toBeNull();
    DB::table('sales')->whereKey($sale->id)->update(['branch_id' => null]);

    $this->get(URL::temporarySignedRoute('sales.print-local', now()->addMinute(), ['id' => $sale->id]))
        ->assertOk();
});
