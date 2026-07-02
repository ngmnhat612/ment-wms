<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Brand;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class BrandPolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách thương hiệu.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết thương hiệu.
     */
    public function view(Account $account, Brand $brand): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo thương hiệu.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được chỉnh sửa thương hiệu.
     */
    public function update(Account $account, Brand $brand): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được xóa thương hiệu.
     */
    public function delete(Account $account, Brand $brand): bool
    {
        return $this->allow($account);
    }
}