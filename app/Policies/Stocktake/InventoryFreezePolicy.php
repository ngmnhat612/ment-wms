<?php

namespace App\Policies\Stocktake;

use App\Models\Master\Account;
use App\Models\Stocktake\InventoryFreeze;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class InventoryFreezePolicy
{
    use HasRoleDepartmentAuthorization;

    public function view(Account $account, InventoryFreeze $freeze): bool
    {
        return true;
    }

    /**
     * Admin hoặc Quản lý bộ phận Kho được đóng băng / mở đóng băng kho.
     */
    public function manage(Account $account, InventoryFreeze $freeze): bool
    {
        return $this->allow($account);
    }
}
