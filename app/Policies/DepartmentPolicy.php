<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Department;

class DepartmentPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách bộ phận.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết bộ phận.
     */
    public function view(Account $account, Department $department): bool
    {
        return true;
    }

    /**
     * Chỉ Admin được tạo bộ phận (ảnh hưởng trực tiếp đến cơ cấu phân quyền).
     */
    public function create(Account $account): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được chỉnh sửa bộ phận.
     */
    public function update(Account $account, Department $department): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được xóa bộ phận.
     */
    public function delete(Account $account, Department $department): bool
    {
        return $account->hasRole('Admin');
    }
}
