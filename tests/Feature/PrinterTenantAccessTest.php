<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\URL;

test('a signed printer request can render an order from another branch', function () {
    $company = Company::create(['name' => 'Restaurante Impresión', 'slug' => 'restaurante-impresion', 'is_active' => true]);
    $branch = Branch::create(['company_id' => $company->id, 'name' => 'Sede Impresión', 'code' => 'PRINT', 'is_active' => true]);
    $category = new Category(['name' => 'Carta impresión']);
    $category->company_id = $company->id;
    $category->save();
    $product = new Product([
        'category_id' => $category->id,
        'name' => 'Plato impresión',
        'price' => 20,
        'stock' => 1,
        'status' => true,
        'requires_kitchen' => true,
        'image' => 'products/default.png',
    ]);
    $product->company_id = $company->id;
    $product->save();
    $table = new Table(['name' => 'Mesa impresión', 'capacity' => 2, 'status' => 'ocupada']);
    $table->branch_id = $branch->id;
    $table->save();
    $order = new Order(['table_id' => $table->id, 'user_id' => User::factory()->create()->id, 'status' => 'abierto']);
    $order->branch_id = $branch->id;
    $order->save();
    $detail = OrderDetail::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'requires_kitchen' => true,
        'price' => 20,
        'subtotal' => 20,
        'cooking_status' => 'pending',
    ]);

    $this->get(URL::temporarySignedRoute('orders.kitchen-print', now()->addMinute(), [
        'id' => $order->id,
        'detail_ids' => [$detail->id],
    ]))->assertOk();
});
