<?php

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;

test('product price cost and availability are independent for each branch', function () {
    $company = Company::firstOrFail();
    $firstBranch = $company->branches()->firstOrFail();
    $category = Category::create(['name' => 'Carta por valores']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Producto por sede',
        'price' => 8,
        'cost' => 2.5,
        'stock' => 10,
        'status' => true,
        'requires_kitchen' => false,
        'image' => 'products/default.png',
    ]);
    $secondBranch = Branch::create([
        'company_id' => $company->id,
        'name' => 'Sede Valores',
        'code' => 'VALORES',
        'is_active' => true,
    ]);
    $secondStock = new BranchProductStock([
        'product_id' => $product->id,
        'stock' => 3,
        'price' => 12,
        'cost' => 5,
        'is_available' => false,
    ]);
    $secondStock->branch_id = $secondBranch->id;
    $secondStock->save();

    $this->withSession(['company_id' => $company->id, 'branch_id' => $secondBranch->id])->get('/');
    $secondBranchProduct = Product::findOrFail($product->id);

    expect((float) $secondBranchProduct->price)->toBe(12.0)
        ->and((float) $secondBranchProduct->cost)->toBe(5.0)
        ->and($secondBranchProduct->status)->toBeFalse()
        ->and(Product::availableInActiveBranch()->whereKey($product)->exists())->toBeFalse();

    $this->withSession(['company_id' => $company->id, 'branch_id' => $firstBranch->id])->get('/');
    $firstBranchProduct = Product::findOrFail($product->id);

    expect((float) $firstBranchProduct->price)->toBe(8.0)
        ->and((float) $firstBranchProduct->cost)->toBe(2.5)
        ->and($firstBranchProduct->status)->toBeTrue()
        ->and(Product::availableInActiveBranch()->whereKey($product)->exists())->toBeTrue();
});
