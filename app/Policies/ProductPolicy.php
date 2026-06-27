<?php

namespace App\Policies;

use App\Enums\DepartmentCode;
use App\Models\Account;
use App\Models\Product;

class ProductPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết.
     */
    public function view(Account $account, Product $product): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý của bộ phận Kho được tạo vật tư.
     */
    public function create(Account $account): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý của bộ phận Kho được chỉnh sửa vật tư.
     */
    public function update(Account $account, Product $product): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý của bộ phận Kho được xóa vật tư.
     */
    public function delete(Account $account, Product $product): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }
}