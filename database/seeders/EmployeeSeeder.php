<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'code'            => 'NV0001',
                'name'            => 'Administrator',
                'unique_name'     => 'Administrator',
                'department_name' => null, // không thuộc bộ phận nào
            ],
            [
                'code'            => 'NV0002',
                'name'            => 'Nguyễn Thủ Kho',
                'unique_name'     => 'Nguyễn Thủ Kho NV0002',
                'department_name' => 'Kho',
            ],
            [
                'code'            => 'NV0003',
                'name'            => 'Trần Nhân Viên Kho',
                'unique_name'     => 'Trần Nhân Viên Kho NV0003',
                'department_name' => 'Kho',
            ],
            [
                'code'            => 'NV0004',
                'name'            => 'Lê Cung Ứng',
                'unique_name'     => 'Lê Cung Ứng NV0004',
                'department_name' => 'Cung ứng',
            ],
        ];

        foreach ($employees as $data) {
            $departmentId = $data['department_name']
                ? DB::table('departments')->where('name', $data['department_name'])->value('id')
                : null;

            Employee::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name'          => $data['name'],
                    'unique_name'   => $data['unique_name'],
                    'phone_number'  => null,
                    'department_id' => $departmentId,
                    'status'        => ActiveStatus::Active->value,
                ]
            );
        }
    }
}