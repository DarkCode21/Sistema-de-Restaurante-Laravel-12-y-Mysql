<?php

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\User;
use Database\Seeders\BrasaNorteDemoSeeder;
use Database\Seeders\GrillDemoSeeder;
use Spatie\Permission\Models\Role;

test('it creates complete demos for two branches', function () {
    $this->seed(GrillDemoSeeder::class);
    $this->seed(BrasaNorteDemoSeeder::class);
    $this->seed(BrasaNorteDemoSeeder::class);

    $company = Company::where('slug', 'parrilla-brasa-norte')->firstOrFail();
    $branch = Branch::where('company_id', $company->id)->where('code', 'BRASA-NORTE')->firstOrFail();
    $primaryBranch = Branch::where('company_id', '!=', $company->id)->firstOrFail();

    expect(Setting::withoutGlobalScopes()->where('company_id', $company->id)->value('company_name'))->toBe('Parrilla Brasa Norte')
        ->and(Role::where('company_id', $company->id)->count())->toBeGreaterThanOrEqual(4)
        ->and(Product::withoutGlobalScopes()->where('company_id', $company->id)->count())->toBeGreaterThan(20)
        ->and((float) Product::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'Parrilla de Pollo - Pecho')->firstOrFail()->branchStocks()->withoutGlobalScopes()->where('branch_id', $branch->id)->value('price'))->toBe(24.0)
        ->and(Promotion::withoutGlobalScopes()->where('company_id', $company->id)->where('name', 'DEMO-BRASA-NORTE-Tarde 12%')->exists())->toBeTrue()
        ->and(Supplier::withoutGlobalScopes()->where('company_id', $company->id)->exists())->toBeTrue()
        ->and(User::where('email', 'mesero@brasanorte.demo.local')->firstOrFail()->companies()->whereKey($company)->exists())->toBeTrue();

    foreach ([$primaryBranch, $branch] as $demoBranch) {
        expect(Purchase::withoutGlobalScopes()->where('branch_id', $demoBranch->id)->exists())->toBeTrue()
            ->and(Expense::withoutGlobalScopes()->where('branch_id', $demoBranch->id)->exists())->toBeTrue()
            ->and(CashRegister::withoutGlobalScopes()->where('branch_id', $demoBranch->id)->exists())->toBeTrue()
            ->and(Order::withoutGlobalScopes()->where('branch_id', $demoBranch->id)->exists())->toBeTrue()
            ->and(Sale::withoutGlobalScopes()->where('branch_id', $demoBranch->id)->exists())->toBeTrue()
            ->and(Table::withoutGlobalScopes()->where('branch_id', $demoBranch->id)->count())->toBeGreaterThan(0);
    }

    expect(User::where('email', 'coordinador@demo.local')->firstOrFail()->branches()->whereKey([$primaryBranch->id, $branch->id])->count())->toBe(2);
});
