<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\Uom;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class UomPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Uom $uom): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, Uom $uom): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, Uom $uom): bool
    {
        return $this->allow($account);
    }
}