<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Employee;

class EmployeePolicy
{
    /**
     * Chỉ Admin được xem danh sách nhân viên.
     */
    public function viewAny(Account $account): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được xem chi tiết hồ sơ nhân viên.
     */
    public function view(Account $account, Employee $employee): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được tạo hồ sơ nhân viên.
     */
    public function create(Account $account): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được chỉnh sửa hồ sơ nhân viên.
     */
    public function update(Account $account, Employee $employee): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được xóa hồ sơ nhân viên.
     */
    public function delete(Account $account, Employee $employee): bool
    {
        return $account->hasRole('Admin');
    }
}