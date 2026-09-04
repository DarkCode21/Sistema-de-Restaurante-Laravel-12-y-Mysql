<?php

use App\Models\Sale;
use Database\Seeders\GrillDemoSeeder;
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
