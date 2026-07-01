<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Sn;

class SnPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách dự án.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết dự án.
     */
    public function view(Account $account, Sn $sn): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý được tạo dự án.
     *
     * NOTE: điều chỉnh isInDepartmentNamed(...) cho đúng bộ phận quản lý dự án
     * trong hệ thống (ví dụ 'Kho', 'Sản xuất'...). Brand dùng 'Kho' làm ví dụ mẫu.
     */
    public function create(Account $account): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý được chỉnh sửa dự án.
     */
    public function update(Account $account, Sn $sn): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý được xóa dự án.
     */
    public function delete(Account $account, Sn $sn): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }
}
