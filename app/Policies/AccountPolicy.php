<?php

namespace App\Policies;

use App\Models\Account;

class AccountPolicy
{
    /**
     * Chỉ Admin được xem danh sách tài khoản.
     */
    public function viewAny(Account $account): bool
    {
        return $account->hasRole('Admin');
    }

    /**
     * Chỉ Admin được xem chi tiết tài khoản.
     */
    public function view(Account $account, Account $target): bool
    {
        return $account->hasRole('Admin');
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
     * - Không tự sửa chính tài khoản của mình qua màn quản trị này.
     * - Không ai được sửa tài khoản is_protected, kể cả Admin khác.
     */
    public function update(Account $account, Account $target): bool
    {
        if (! $account->hasRole('Admin')) {
            return false;
        }

        if ($target->is_protected) {
            return false;
        }

        return $account->id !== $target->id;
    }

    /**
     * Chỉ Admin được xóa tài khoản đăng nhập.
     * - Không tự xoá chính mình.
     * - Không xoá tài khoản is_protected.
     * - Không xoá Admin cuối cùng còn lại trong hệ thống.
     */
    public function delete(Account $account, Account $target): bool
    {
        if (! $account->hasRole('Admin')) {
            return false;
        }

        if ($account->id === $target->id) {
            return false;
        }

        if ($target->is_protected) {
            return false;
        }

        if ($target->hasRole('Admin') && Account::role('Admin')->count() <= 1) {
            return false;
        }

        return true;
    }
}