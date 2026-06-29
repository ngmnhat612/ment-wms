<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Warehouse;

class WarehousePolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách kho.
     */
    public function viewAny(Account $account): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết kho.
     */
    public function view(Account $account, Warehouse $warehouse): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin hoặc Quản lý bộ phận Kho được tạo kho.
     */
    public function create(Account $account): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin hoặc Quản lý bộ phận Kho được chỉnh sửa kho.
     */
    public function update(Account $account, Warehouse $warehouse): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được xóa kho.
     */
    public function delete(Account $account, Warehouse $warehouse): bool
    {
        return $account->hasRole('Admin');
    }
}