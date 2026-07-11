<?php

namespace App\Policies\Stocktake;

use App\Models\Master\Account;
use App\Models\Stocktake\InventoryCheck;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class InventoryCheckPolicy
{
    use HasRoleDepartmentAuthorization;

    /**
     * Mọi user đã đăng nhập đều xem được danh sách phiếu kiểm kê.
     */
    public function viewAny(Account $account): bool
    {
        return true;
    }

    /**
     * Mọi user đã đăng nhập đều xem được chi tiết phiếu kiểm kê.
     */
    public function view(Account $account, InventoryCheck $inventoryCheck): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo phiếu kiểm kê.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, InventoryCheck $inventoryCheck): bool
    {
        return $this->allow($account);
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được bắt đầu / hoàn thành kiểm kê.
     */
    public function manage(Account $account, InventoryCheck $inventoryCheck): bool
    {
        return $this->allow($account);
    }

    public function cancel(Account $account, InventoryCheck $inventoryCheck): bool
    {
        return $this->allow($account);
    }
}
