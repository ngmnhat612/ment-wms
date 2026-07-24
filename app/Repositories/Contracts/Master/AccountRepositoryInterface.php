<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Account;

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

    /**
     * Kiểm tra tên đăng nhập đã tồn tại chưa.
     */
    public function usernameExists(string $username): bool;
}
