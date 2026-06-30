<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Supplier;

class SupplierPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách nhà cung cấp.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết nhà cung cấp.
     */
    public function view(Account $account, Supplier $supplier): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo nhà cung cấp.
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
     * Admin hoặc Quản lý bộ phận Kho được chỉnh sửa nhà cung cấp.
     */
    public function update(Account $account, Supplier $supplier): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được xóa nhà cung cấp.
     */
    public function delete(Account $account, Supplier $supplier): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }
}
