<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PreparationStation;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use App\Support\TenantSeedContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class BrasaNorteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::firstOrCreate(
            ['slug' => 'parrilla-brasa-norte'],
            ['name' => 'Parrilla Brasa Norte', 'is_active' => true],
        );
        $branch = $company->branches()->firstOrCreate(
            ['code' => 'BRASA-NORTE'],
            ['name' => 'Sede Brasa Norte', 'address' => 'Av. Los Próceres 880', 'phone' => '01 555 0199', 'is_active' => true],
        );

        Setting::withoutGlobalScopes()->updateOrCreate(['company_id' => $company->id], [
            'company_name' => $company->name,
            'company_email' => 'hola@brasanorte.demo.local',
            'company_phone' => '01 555 0199',
            'company_address' => 'Av. Los Próceres 880',
            'currency_simbol' => 'S/',
            'default_tax_rate' => 18,
            'timezone' => 'America/Lima',
            'direct_printing' => false,
        ]);

        $primaryBranch = Branch::query()
            ->where('company_id', '!=', $company->id)
            ->where('is_active', true)
            ->first();

        if ($primaryBranch && !Order::withoutGlobalScopes()->where('branch_id', $primaryBranch->id)->exists()) {
            app(GrillDemoSeeder::class)->forBranch($primaryBranch->company, $primaryBranch, [
                'operator' => ['email' => 'admin@demo.local', 'name' => 'Administradora Demo'],
                'grill_cook' => ['email' => 'parrillero@demo.local', 'name' => 'Parrillero Demo'],
                'kitchen_cook' => ['email' => 'cocina@demo.local', 'name' => 'Cocinero Demo'],
            ])->run();
        }

        if ($primaryBranch) {
            TenantSeedContext::run($primaryBranch->company_id, $primaryBranch->id, function () use ($primaryBranch): void {
                $this->call(PermissionSeeder::class);

                if (!Expense::query()->exists()) {
                    Expense::firstOrCreate(['concept' => 'DEMO-MULTISEDE-Limpieza'], [
                        'cash_register_id' => CashRegister::query()->where('status', 'open')->value('id'),
                        'payment_method_id' => PaymentMethod::query()->where('is_efectivo', true)->value('id'),
                        'user_id' => $primaryBranch->users()->where(fn ($query) => $query->whereNull('type')->orWhere('type', '!=', 'client'))->value('users.id'),
                        'description' => 'Limpieza operativa de demostración.',
                        'amount' => 24.00,
                        'expense_date' => today()->setTime(9, 15),
                    ]);
                }
            });
        }

        TenantSeedContext::run($company->id, $branch->id, function () use ($company, $branch, $primaryBranch): void {
            $this->call(PermissionSeeder::class);
            $this->call(TableSeeder::class);

            app(GrillDemoSeeder::class)->forBranch($company, $branch, [
                'operator' => ['email' => 'admin@brasanorte.demo.local', 'name' => 'Administradora Brasa Norte'],
                'grill_cook' => ['email' => 'parrillero@brasanorte.demo.local', 'name' => 'Parrillero Brasa Norte'],
                'kitchen_cook' => ['email' => 'cocina@brasanorte.demo.local', 'name' => 'Cocinero Brasa Norte'],
            ])->run();

            setPermissionsTeamId($company->id);
            $staff = [
                ['email' => 'admin@brasanorte.demo.local', 'role' => 'admin'],
                ['email' => 'parrillero@brasanorte.demo.local', 'role' => 'cocinero'],
                ['email' => 'cocina@brasanorte.demo.local', 'role' => 'cocinero'],
                ['email' => 'mesero@brasanorte.demo.local', 'name' => 'Mesero Brasa Norte', 'role' => 'mesero'],
                ['email' => 'cajera@brasanorte.demo.local', 'name' => 'Cajera Brasa Norte', 'role' => 'cajero'],
            ];

            foreach ($staff as $member) {
                $user = User::firstOrCreate(['email' => $member['email']], [
                    'name' => $member['name'] ?? ucfirst($member['role']) . ' Brasa Norte',
                    'password' => Hash::make('demo12345'),
                    'type' => 'user',
                    'email_verified_at' => now(),
                ]);
                $user->companies()->syncWithoutDetaching([$company->id]);
                $user->branches()->syncWithoutDetaching([$branch->id]);
                $user->syncRoles([Role::where('company_id', $company->id)->where('name', $member['role'])->firstOrFail()]);
            }

            $coordinator = User::firstOrCreate(['email' => 'coordinador@demo.local'], [
                'name' => 'Coordinador Demo',
                'password' => Hash::make('demo12345'),
                'type' => 'user',
                'email_verified_at' => now(),
            ]);
            $coordinator->companies()->syncWithoutDetaching(array_filter([$company->id, $primaryBranch?->company_id]));
            $coordinator->branches()->syncWithoutDetaching(array_filter([$branch->id, $primaryBranch?->id]));

            foreach (array_filter([$company->id, $primaryBranch?->company_id]) as $companyId) {
                setPermissionsTeamId($companyId);
                $coordinator->assignRole(Role::where('company_id', $companyId)->where('name', 'admin')->firstOrFail());
            }
            setPermissionsTeamId($company->id);

            $stations = PreparationStation::whereIn('name', ['Cocina', 'Parrilla'])->get()->keyBy('name');
            $stations['Parrilla']->users()->syncWithoutDetaching([User::where('email', 'parrillero@brasanorte.demo.local')->value('id')]);
            $stations['Cocina']->users()->syncWithoutDetaching([User::where('email', 'cocina@brasanorte.demo.local')->value('id')]);

            foreach ([
                ['Ana Brasa Norte', 'cliente.ana@brasanorte.demo.local', '70112233', '999 100 101'],
                ['Carlos Brasa Norte', 'cliente.carlos@brasanorte.demo.local', '70112234', '999 100 102'],
            ] as [$name, $email, $document, $phone]) {
                $customer = User::firstOrCreate(['email' => $email], [
                    'name' => $name,
                    'document_number' => $document,
                    'phone' => $phone,
                    'type' => 'client',
                    'password' => Hash::make('demo12345'),
                    'email_verified_at' => now(),
                ]);
                $customer->companies()->syncWithoutDetaching([$company->id]);
            }

            Product::query()->get()->each(function (Product $product): void {
                $price = (float) $product->getRawOriginal('price');
                $product->branchStocks()->updateOrCreate([], [
                    'price' => $price > 0 ? round($price * 1.1, 2) : 0,
                    'cost' => $product->getRawOriginal('cost'),
                    'is_available' => $product->name !== 'Salsa de Ají',
                ]);
            });
            Promotion::updateOrCreate(['name' => 'DEMO-BRASA-NORTE-Tarde 12%'], [
                'product_id' => Product::where('name', 'Costillas de Cerdo BBQ')->value('id'),
                'discount_type' => 'percent',
                'value' => 12,
                'starts_at' => today()->setTime(15, 0),
                'ends_at' => today()->setTime(18, 0),
                'active' => true,
            ]);

            $areaId = Table::whereNotNull('dining_area_id')->value('dining_area_id');
            $floorId = Table::whereNotNull('restaurant_floor_id')->value('restaurant_floor_id');
            Table::whereNull('dining_area_id')->update(['dining_area_id' => $areaId, 'restaurant_floor_id' => $floorId]);
        });
    }
}
