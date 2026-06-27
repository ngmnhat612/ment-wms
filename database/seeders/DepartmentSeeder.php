<?php

namespace Database\Seeders;

use App\Enums\DepartmentCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'BP0001', 'name' => 'Kho'],
            ['code' => 'BP0002', 'name' => 'Cung ứng'],
            ['code' => 'BP0003', 'name' => 'Điện'],
        ];

        foreach ($departments as $dept) {
            DB::table('departments')->updateOrInsert(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'status' => 1],
            );
        }
    }
}