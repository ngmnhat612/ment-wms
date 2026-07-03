<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\Department;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class DepartmentPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Department $department): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, Department $department): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, Department $department): bool
    {
        return $this->allow($account);
    }
}