<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear rol admin si no existe
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Crear usuario admin
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
        ]);

        $admin->assignRole($adminRole);

        $user = User::create([
            'name' => 'Mesero',
            'email' => 'mesero@gmail.com',
            'password' => Hash::make('mesero123'),
        ]);
        $userRole = Role::firstOrCreate(['name' => 'mesero']);
        $user->assignRole($userRole);

        //Cocinero
        $user = User::create([
            'name' => 'Cocinero',
            'email' => 'cocinero@gmail.com',
            'password' => Hash::make('cocinero123'),
        ]);
        $userRole = Role::firstOrCreate(['name' => 'cocinero']);
        $user->assignRole($userRole);

        $user = User::create([
            'name' => 'Cajero',
            'email' => 'cajero@gmail.com',
            'password' => Hash::make('cajero123'),
        ]);
        $userRole = Role::firstOrCreate(['name' => 'cajero']);
        $user->assignRole($userRole);
    }
}
