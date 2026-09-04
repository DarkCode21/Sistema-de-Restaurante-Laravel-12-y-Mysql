<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Table;

test('catalogs and operations stay in the selected tenant context', function () {
    $firstCompany = Company::firstOrFail();
    $firstBranch = $firstCompany->branches()->firstOrFail();
    $firstCategory = Category::create(['name' => 'Carta principal']);
    $firstTable = Table::create(['name' => 'Mesa principal']);

    $secondCompany = Company::create([
        'name' => 'Restaurante Norte',
        'slug' => 'restaurante-norte',
        'is_active' => true,
    ]);
    $secondBranch = Branch::create([
        'company_id' => $secondCompany->id,
        'name' => 'Sede Norte',
        'code' => 'NORTE',
        'is_active' => true,
    ]);
    $secondCategory = new Category(['name' => 'Carta norte']);
    $secondCategory->company_id = $secondCompany->id;
    $secondCategory->save();
    $secondTable = new Table(['name' => 'Mesa norte']);
    $secondTable->branch_id = $secondBranch->id;
    $secondTable->save();

    $this->withSession([
        'company_id' => $secondCompany->id,
        'branch_id' => $secondBranch->id,
    ])->get('/');

    expect($firstCategory->company_id)->toBe($firstCompany->id)
        ->and($firstTable->branch_id)->toBe($firstBranch->id)
        ->and(Category::pluck('id')->all())->toBe([$secondCategory->id])
        ->and(Table::pluck('id')->all())->toBe([$secondTable->id]);
});
