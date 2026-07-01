<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Employee;

class EmployeePolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách nhân viên.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết nhân viên.
     */
    public function view(Account $account, Employee $employee): bool
    {
        return true;
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
