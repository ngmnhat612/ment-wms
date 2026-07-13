<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\WarehouseEmployee;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class WarehouseEmployeePolicy
{
    use HasRoleDepartmentAuthorization;

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, WarehouseEmployee $warehouseEmployee): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, WarehouseEmployee $warehouseEmployee): bool
    {
        return $this->allow($account);
    }
}