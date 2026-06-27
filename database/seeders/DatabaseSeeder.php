<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Vai trò
            RoleSeeder::class,

            // 2. Bộ phận
            DepartmentSeeder::class,

            // 3. Kho mặc định + virtual root location
            WarehouseSeeder::class,

            // 4. Nhân viên
            EmployeeSeeder::class,

            // 5. Tài khoản đăng nhập + gán role
            AccountSeeder::class,

            // 6. Phân công nhân viên vào kho
            WarehouseEmployeeSeeder::class,

            // 7. Danh mục vật tư
            CategorySeeder::class,

            // 8. Đơn vị tính
            UomSeeder::class,

            // 9. Vật tư
            ProductSeeder::class,
        ]);
    }
}