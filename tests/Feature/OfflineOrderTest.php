<?php

use App\Models\BranchProductStock;
use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\PrintJob;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function offlineWaiter(): array
{
    $company = Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->branches()->attach($branch);
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ordenes.crear']));
    $user->assignRole(Role::firstOrCreate(['name' => 'mesero']));

    return [$user, $company, $branch];
}

it('synchronizes an offline order once and consumes stock in its branch', function () {
    [$user, $company, $branch] = offlineWaiter();
    Setting::create(['company_name' => 'Ceviche offline', 'direct_printing' => true, 'printer_name' => 'Cocina offline']);
    $category = Category::create(['name' => 'Carta offline']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Limonada offline',
        'price' => 10,
        'stock' => 5,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $token = (string) Str::uuid();
    $payload = [
        'token' => $token,
        'user_id' => $user->id,
        'branch_id' => $branch->id,
        'order_type' => 'pickup',
        'customer_name' => 'Ana',
        'items' => [['product_id' => $product->id, 'quantity' => 2, 'notes' => 'Sin hielo']],
    ];

    $this->actingAs($user)
        ->withSession(['company_id' => $company->id, 'branch_id' => $branch->id])
        ->postJson(route('orders.offline'), $payload)
        ->assertCreated()
        ->assertJsonPath('synchronized', false)
        ->assertJsonPath('print_jobs.0.printer_name', 'Cocina offline');

    $this->actingAs($user)
        ->withSession(['company_id' => $company->id, 'branch_id' => $branch->id])
        ->postJson(route('orders.offline'), $payload)
        ->assertOk()
        ->assertJsonPath('synchronized', true);

    $order = Order::withoutGlobalScopes()->where('offline_token', $token)->firstOrFail();

    expect($order->details)->toHaveCount(1)
        ->and((float) $order->total)->toBe(20.0)
        ->and((float) BranchProductStock::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock'))->toBe(3.0)
        ->and(PrintJob::where('order_id', $order->id)->where('status', 'queued')->count())->toBe(1);
});

it('rejects offline synchronization from a non-waiter', function () {
    $company = Company::firstOrFail();
    $branch = $company->branches()->firstOrFail();
    $user = User::factory()->create();
    $user->companies()->attach($company);
    $user->branches()->attach($branch);
    $user->givePermissionTo(Permission::firstOrCreate(['name' => 'ordenes.crear']));

    $this->actingAs($user)
        ->withSession(['company_id' => $company->id, 'branch_id' => $branch->id])
        ->postJson(route('orders.offline'), [])
        ->assertForbidden();
});

it('rejects combinations that require online configuration', function () {
    [$user, $company, $branch] = offlineWaiter();
    $category = Category::create(['name' => 'Carta configurada']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Combo offline',
        'price' => 25,
        'stock' => 1,
        'status' => true,
        'is_combo' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);

    $this->actingAs($user)
        ->withSession(['company_id' => $company->id, 'branch_id' => $branch->id])
        ->postJson(route('orders.offline'), [
            'token' => (string) Str::uuid(),
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'order_type' => 'pickup',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Los combos y productos con opciones deben registrarse con conexion.');
});
