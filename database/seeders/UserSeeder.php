<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrFail();
        $branch = $company->branches()->firstOrFail();
        setPermissionsTeamId($company->id);

        $adminRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Crear usuario admin
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
        ]);

        $admin->assignRole($adminRole);

        $waiter = User::create([
            'name' => 'Mesero',
            'email' => 'mesero@gmail.com',
            'password' => Hash::make('mesero123'),
        ]);
        $userRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'mesero',
            'guard_name' => 'web',
        ]);
        $waiter->assignRole($userRole);

        //Cocinero
        $cook = User::create([
            'name' => 'Cocinero',
            'email' => 'cocinero@gmail.com',
            'password' => Hash::make('cocinero123'),
        ]);
        $userRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'cocinero',
            'guard_name' => 'web',
        ]);
        $cook->assignRole($userRole);

        $cashier = User::create([
            'name' => 'Cajero',
            'email' => 'cajero@gmail.com',
            'password' => Hash::make('cajero123'),
        ]);
        $userRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'cajero',
            'guard_name' => 'web',
        ]);
        $cashier->assignRole($userRole);

        foreach ([$admin, $waiter, $cook, $cashier] as $user) {
            $user->companies()->syncWithoutDetaching([$company->id]);
            $user->branches()->syncWithoutDetaching([$branch->id]);
        }
    }
}
