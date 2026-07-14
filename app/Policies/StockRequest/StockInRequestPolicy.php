<?php

namespace App\Policies\StockRequest;

use App\Models\Master\Account;
use App\Models\StockRequest\StockInRequest;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class StockInRequestPolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách yêu cầu nhập.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết yêu cầu nhập.
     */
    public function view(Account $account, StockInRequest $stockInRequest): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo yêu cầu nhập.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được hoàn tất yêu cầu nhập
     * (Draft -> Completed).
     */
    public function complete(Account $account, StockInRequest $stockInRequest): bool
    {
        return $this->allow($account);
    }

    /**
     * Chỉ Admin được xóa yêu cầu nhập.
     */
    public function delete(Account $account, StockInRequest $stockInRequest): bool
    {
        return $account->hasRole('Admin');
    }

    public function update(Account $account, StockInRequest $stockInRequest): bool
    {
        return $this->allow($account);
    }

    public function cancel(Account $account, StockInRequest $stockInRequest): bool
    {
        return $this->allow($account);
    }
}