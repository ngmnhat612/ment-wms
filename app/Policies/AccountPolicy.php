<?php

namespace App\Policies;

use App\Models\Account;

class AccountPolicy
{
    /**
     * Mọi user đã đăng nhập đều xem được danh sách tài khoản.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Account $target): bool
    {
        return true;
    }

    /**
     * Chỉ Admin được tạo tài khoản đăng nhập cho nhân viên.
     */
    public function create(Account $account): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được đổi vai trò / mật khẩu / trạng thái tài khoản.
     */
    public function update(Account $account, Account $target): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được xóa tài khoản đăng nhập.
     */
    public function delete(Account $account, Account $target): bool
    {
        return $account->hasRole('Admin');
    }
}