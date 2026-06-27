<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Category;

class CategoryPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách danh mục.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết danh mục.
     */
    public function view(Account $account, Category $category): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo danh mục.
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
     * Admin hoặc Quản lý bộ phận Kho được chỉnh sửa danh mục.
     */
    public function update(Account $account, Category $category): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được xóa danh mục.
     */
    public function delete(Account $account, Category $category): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }
}