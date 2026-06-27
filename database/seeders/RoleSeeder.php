<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder {
    public function run(): void
    {
        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Tạo roles
        Role::firstOrCreate(['name' => 'Admin',     'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Quản lý',   'guard_name' => 'web']); 
        Role::firstOrCreate(['name' => 'Nhân viên', 'guard_name' => 'web']);
    }
}