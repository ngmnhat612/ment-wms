<?php

namespace App\Policies\Stocktake;

use App\Models\Master\Account;
use App\Models\Stocktake\StockAdjustment;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class StockAdjustmentPolicy
{
    use HasRoleDepartmentAuthorization;

    public function view(Account $account, StockAdjustment $adjustment): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được tạo phiếu điều chỉnh.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    /**
     * Chỉ Admin hoặc Quản lý bộ phận Kho được xác nhận điều chỉnh
     * (thao tác cập nhật tồn kho thật, không thể hoàn tác).
     */
    public function complete(Account $account, StockAdjustment $adjustment): bool
    {
        return $this->allow($account);
    }

    public function cancel(Account $account, StockAdjustment $adjustment): bool
    {
        return $this->allow($account);
    }
}
