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
        if (app()->environment('production')) {
            return;
        }

        $company = Company::query()->firstOrFail();
        $branch = $company->branches()->firstOrFail();
        setPermissionsTeamId($company->id);

        $adminRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        // Crear usuario admin
        $admin = User::firstOrCreate(['email' => 'admin@gmail.com'], [
            'name' => 'Administrador',
            'password' => Hash::make('admin123'),
            'type' => 'user',
            'email_verified_at' => now(),
        ]);

        $admin->assignRole($adminRole);

        $waiterRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'mesero',
            'guard_name' => 'web',
        ]);
        $waiters = collect([
            ['email' => 'mesero@gmail.com', 'name' => 'Carlos Paredes', 'password' => 'mesero123'],
            ['email' => 'mesera@gmail.com', 'name' => 'Lucía Torres', 'password' => 'mesera123'],
            ['email' => 'mesero2@gmail.com', 'name' => 'Diego Ramos', 'password' => 'mesero2123'],
        ])->map(fn (array $waiter) => User::firstOrCreate(['email' => $waiter['email']], [
            'name' => $waiter['name'],
            'password' => Hash::make($waiter['password']),
            'type' => 'user',
            'email_verified_at' => now(),
        ]));
        $waiters->each(fn (User $waiter) => $waiter->assignRole($waiterRole));

        //Cocinero
        $cook = User::firstOrCreate(['email' => 'cocinero@gmail.com'], [
            'name' => 'Cocinero',
            'password' => Hash::make('cocinero123'),
            'type' => 'user',
            'email_verified_at' => now(),
        ]);
        $userRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'cocinero',
            'guard_name' => 'web',
        ]);
        $cook->assignRole($userRole);

        $cashier = User::firstOrCreate(['email' => 'cajero@gmail.com'], [
            'name' => 'Cajero',
            'password' => Hash::make('cajero123'),
            'type' => 'user',
            'email_verified_at' => now(),
        ]);
        $userRole = Role::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'cajero',
            'guard_name' => 'web',
        ]);
        $cashier->assignRole($userRole);

        foreach ([$admin, ...$waiters->all(), $cook, $cashier] as $user) {
            $user->companies()->syncWithoutDetaching([$company->id]);
            $user->branches()->syncWithoutDetaching([$branch->id]);
        }
    }
}
