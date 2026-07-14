<?php

namespace App\Policies\StockRequest;

use App\Models\Master\Account;
use App\Models\StockRequest\StockOutRequest;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class StockOutRequestPolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách yêu cầu xuất.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết yêu cầu xuất.
     */
    public function view(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo yêu cầu xuất.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được hoàn tất yêu cầu xuất
     * (Draft -> Completed).
     */
    public function complete(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $this->allow($account);
    }

    /**
     * Chỉ Admin được xóa yêu cầu xuất.
     */
    public function delete(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $account->hasRole('Admin');
    }

    public function update(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $this->allow($account);
    }

    public function cancel(Account $account, StockOutRequest $stockOutRequest): bool
    {
        return $this->allow($account);
    }
}