<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Models\Master\Account;
use App\Models\Master\Employee;
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
                'is_protected'  => true,
            ],
            [
                'employee_code' => 'NV0002',
                'username'      => 'manager@warehouse.local',
                'password'      => 'Test@1234',
                'role'          => 'Quản lý',
                'is_protected'  => false,
            ],
            [
                'employee_code' => 'NV0003',
                'username'      => 'employee@warehouse.local',
                'password'      => 'Test@1234',
                'role'          => 'Nhân viên',
                'is_protected'  => false,
            ],
            [
                'employee_code' => 'NV0004',
                'username'      => 'otheremployee@warehouse.local',
                'password'      => 'Test@1234',
                'role'          => 'Nhân viên',
                'is_protected'  => false,
            ],
        ];

        foreach ($accounts as $data) {
            $employee = Employee::where('code', $data['employee_code'])->firstOrFail();

            $account = Account::firstOrCreate(
                ['username' => $data['username']],
                [
                    'employee_id'  => $employee->id,
                    'password'     => Hash::make($data['password']),
                    'status'       => ActiveStatus::Active->value,
                    'is_protected' => $data['is_protected'],
                ]
            );

            // Đảm bảo is_protected đúng ngay cả khi account đã tồn tại từ trước
            if ($account->is_protected !== $data['is_protected']) {
                $account->update(['is_protected' => $data['is_protected']]);
            }

            // Gán role qua Spatie (syncRoles để tránh trùng khi chạy lại)
            $account->syncRoles([$data['role']]);
        }
    }
}
