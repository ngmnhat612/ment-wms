<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['code' => 'TS', 'name' => 'Công cụ dụng cụ, tài sản'],
            ['code' => 'CK', 'name' => 'Cơ khí'],
            ['code' => 'CH', 'name' => 'Chung, tiêu hao'],
            ['code' => 'DD', 'name' => 'Dây điện, máng điện, ray'],
            ['code' => 'TL', 'name' => 'Dụng cụ xuất theo máy'],
            ['code' => 'DI', 'name' => 'Điện'],
            ['code' => 'KI', 'name' => 'Kim'],
            ['code' => 'KN', 'name' => 'Khí nén'],
            ['code' => 'MA', 'name' => 'Mạch'],
            ['code' => 'NC', 'name' => 'Nước'],
            ['code' => 'SP', 'name' => 'Sản Phẩm'],
            ['code' => 'OS', 'name' => 'Vật tư cũ có thể lắp máy'],
            ['code' => 'TN', 'name' => 'Vật tư RD thử nghiệm'],
            ['code' => 'BL', 'name' => 'Bu lông'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'code'      => $category['code'],
                'name'      => $category['name'],
                'parent_id' => null,
                'note'      => null,
                'status'    => 1,
            ]);
        }
    }
}