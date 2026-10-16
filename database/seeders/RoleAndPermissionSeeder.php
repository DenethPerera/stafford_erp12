<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Standardized CRUD permissions for our current menus
        $permissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            
            'view profile',
            'create profile',
            'edit profile',
            'delete profile',
            
            'view lab_reports',
            'create lab_reports',
            'edit lab_reports',
            'delete lab_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }

        // create roles and assign created permissions
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $roleSuperAdmin->syncPermissions(Permission::all());

        $roleLab = Role::firstOrCreate(['name' => 'lab']);
        $roleLab->syncPermissions([
            'view profile',
            'edit profile',
            'view lab_reports',
            'create lab_reports',
            'edit lab_reports',
            'delete lab_reports',
        ]);
    }
}
