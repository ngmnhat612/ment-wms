<?php

namespace App\Policies\StockMovement;

use App\Models\Master\Account;
use App\Models\StockMovement\StockReceipt;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class StockReceiptPolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách phiếu nhập.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết phiếu nhập.
     */
    public function view(Account $account, StockReceipt $stockReceipt): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo phiếu nhập.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được duyệt phiếu nhập.
     */
    public function approve(Account $account, StockReceipt $stockReceipt): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, StockReceipt $stockReceipt): bool
    {
        return $this->allow($account);
    }

    public function cancel(Account $account, StockReceipt $stockReceipt): bool
    {
        return $this->allow($account);
    }
}