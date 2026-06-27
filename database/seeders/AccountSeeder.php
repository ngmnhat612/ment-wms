<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Models\Account;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'employee_code' => 'NV0001',
                'username'      => 'admin@warehouse.local',
                'password'      => 'Admin@1234',
                'role'          => 'Admin',
            ],
            [
                'employee_code' => 'NV0002',
                'username'      => 'manager@warehouse.local',
                'password'      => 'Test@1234',
                'role'          => 'Quản lý',
            ],
            [
                'employee_code' => 'NV0003',
                'username'      => 'employee@warehouse.local',
                'password'      => 'Test@1234',
                'role'          => 'Nhân viên',
            ],
            [
                'employee_code' => 'NV0004',
                'username'      => 'otheremployee@warehouse.local',
                'password'      => 'Test@1234',
                'role'          => 'Nhân viên',
            ],
        ];

        foreach ($accounts as $data) {
            $employee = Employee::where('code', $data['employee_code'])->firstOrFail();

            $account = Account::firstOrCreate(
                ['username' => $data['username']],
                [
                    'employee_id' => $employee->id,
                    'password'    => Hash::make($data['password']),
                    'status'      => ActiveStatus::Active->value,
                ]
            );

            // Gán role qua Spatie (syncRoles để tránh trùng khi chạy lại)
            $account->syncRoles([$data['role']]);
        }
    }
}