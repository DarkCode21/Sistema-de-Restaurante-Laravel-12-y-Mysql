<?php

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;

test('product stock is independent for each branch', function () {
    $company = Company::firstOrFail();
    $firstBranch = $company->branches()->firstOrFail();
    $category = Category::create(['name' => 'Carta por sede']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Bebida por sede',
        'price' => 8,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $secondBranch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Sede Inventario',
        'code' => 'INVENTARIO',
        'is_active' => true,
    ]);
    $secondStock = new BranchProductStock(['product_id' => $product->id, 'stock' => 3]);
    $secondStock->branch_id = $secondBranch->id;
    $secondStock->save();

    $this->withSession([
        'company_id' => $company->id,
        'branch_id' => $secondBranch->id,
    ])->get('/');

    BranchProductStock::query()->where('product_id', $product->id)->firstOrFail()->decrement('stock');

    expect((float) Product::findOrFail($product->id)->branchStocks()->value('stock'))->toBe(2.0)
        ->and((float) BranchProductStock::withoutGlobalScopes()
            ->where('branch_id', $firstBranch->id)
            ->where('product_id', $product->id)
            ->value('stock'))->toBe(10.0);
});
