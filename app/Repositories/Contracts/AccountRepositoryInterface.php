<?php

namespace App\Repositories\Contracts;

use App\Models\Account;

interface AccountRepositoryInterface
{
    /**
     * Tạo mới tài khoản đăng nhập.
     */
    public function create(array $data): Account;

    /**
     * Cập nhật tài khoản (mật khẩu, trạng thái...).
     */
    public function update(Account $account, array $data): bool;

    /**
     * Xóa tài khoản (giữ lại hồ sơ nhân viên).
     */
    public function delete(Account $account): bool;
}
