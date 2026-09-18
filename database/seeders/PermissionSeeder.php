<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Support\TenantSeedContext;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = TenantSeedContext::companyId() ?? Company::query()->where('is_active', true)->value('id');
        setPermissionsTeamId($companyId);
        $rolesPermissions = [
            'admin' => [
                'categorias.ver',
                'categorias.crear',
                'categorias.editar',
                'categorias.eliminar',
                'cajas.ver',
                'cajas.crear',
                'cajas.editar',
                'cajas.eliminar',
                'cajas.cerrar',
                'cajas.movimientos',
                'productos.ver',
                'productos.crear',
                'productos.editar',
                'productos.eliminar',
                'mesas.ver',
                'mesas.crear',
                'mesas.editar',
                'mesas.eliminar',
                'ordenes.ver',
                'ordenes.crear',
                'ordenes.cobrar',
                'ventas.ver',
                'ventas.reportes',
                'payment_methods.ver',
                'payment_methods.crear',
                'payment_methods.editar',
                'payment_methods.eliminar',
                'gastos.ver',
                'gastos.crear',
                'gastos.editar',
                'gastos.eliminar',
                'gastos.reportes',
                'usuarios.ver',
                'usuarios.crear',
                'usuarios.editar',
                'usuarios.eliminar',
                'empresa.editar',
                'empresa.tablero',
                'roles.ver', 'roles.editar'
            ],
            'cajero' => [
                'ordenes.ver',
                'ordenes.cobrar',
                'ventas.ver',
                'ventas.reportes',
                'payment_methods.ver',
                'productos.ver',
                'cajas.ver',
                'cajas.cerrar',
                'cajas.movimientos',
                'cajas.crear',
                'cajas.editar',
            ],
            'cocinero' => [
                'ordenes.ver',
                'productos.ver',
            ],
            'mesero' => [
                'ordenes.ver',
                'ordenes.crear',
                'ordenes.cobrar',
                'mesas.ver',
                'productos.ver',
                'productos.crear',
                'productos.editar',
                'productos.eliminar',
            ],
        ];

        foreach ($rolesPermissions as $roleName => $permissions) {
            $role = Role::where('company_id', $companyId)->where('name', $roleName)->first();
            if (!$role) {
                $role = new Role();
                $role->company_id = $companyId;
                $role->name = $roleName;
                $role->guard_name = 'web';
                $role->save();
            }

            foreach ($permissions as $permName) {
                // Crear permiso si no existe
                $permission = Permission::firstOrCreate(['name' => $permName]);
                // Asignar permiso al rol
                $role->givePermissionTo($permission);
            }
        }
    }
}
