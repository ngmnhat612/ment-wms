<?php

namespace App\Policies\StockRequest;

use App\Models\Master\Account;
use App\Models\StockRequest\StockOutRequest;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class StockOutRequestPolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách phiếu yêu cầu xuất kho.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết phiếu yêu cầu xuất kho.
     */
    public function view(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo phiếu yêu cầu xuất kho.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được hoàn thành phiếu yêu cầu.
     */
    public function complete(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $this->allow($account);
    }

    public function cancel(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $this->allow($account);
    }

    /**
     * Chỉ Admin được xóa phiếu yêu cầu xuất kho.
     */
    public function delete(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $account->hasRole('Admin');
    }
}
