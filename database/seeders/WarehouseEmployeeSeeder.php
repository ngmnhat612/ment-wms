<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Warehouse;
use App\Models\WarehouseEmployee;
use Illuminate\Database\Seeder;

class WarehouseEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Warehouse::where('code', 'WH0001')->firstOrFail();

        // Gán 4 nhân viên kho vào WH0001 (NV004 là bộ phận khác, vẫn truy cập vào kho được)
        $codes = ['NV0001', 'NV0002', 'NV0003', 'NV0004'];

        foreach ($codes as $code) {
            $employee = Employee::where('code', $code)->firstOrFail();

            WarehouseEmployee::firstOrCreate(
                [
                    'warehouse_id' => $warehouse->id,
                    'employee_id'  => $employee->id,
                ],
                ['is_primary' => true]
            );
        }

        // Gán thủ kho NV002 làm manager của kho
        $thuKho = Employee::where('code', 'NV0002')->firstOrFail();
        $warehouse->update(['manager_id' => $thuKho->id]);
    }
}