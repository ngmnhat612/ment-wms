<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Location;

class LocationPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách vị trí.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết vị trí.
     */
    public function view(Account $account, Location $location): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo vị trí.
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
     * Admin hoặc Quản lý bộ phận Kho được chỉnh sửa vị trí.
     */
    public function update(Account $account, Location $location): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được xóa vị trí.
     */
    public function delete(Account $account, Location $location): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }
}
