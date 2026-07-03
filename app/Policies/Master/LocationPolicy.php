<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\Location;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class LocationPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Location $location): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, Location $location): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, Location $location): bool
    {
        return $this->allow($account);
    }
}