<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\UomConversion;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class UomConversionPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, UomConversion $uomConversion): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, UomConversion $uomConversion): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, UomConversion $uomConversion): bool
    {
        return $this->allow($account);
    }
}