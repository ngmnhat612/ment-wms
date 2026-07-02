<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Uom;
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