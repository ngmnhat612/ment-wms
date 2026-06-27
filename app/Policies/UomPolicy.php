<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Uom;

class UomPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách đơn vị tính.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết đơn vị tính.
     */
    public function view(Account $account, Uom $uom): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo đơn vị tính.
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
     * Admin hoặc Quản lý bộ phận Kho được chỉnh sửa đơn vị tính.
     */
    public function update(Account $account, Uom $uom): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được xóa đơn vị tính.
     */
    public function delete(Account $account, Uom $uom): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }
}