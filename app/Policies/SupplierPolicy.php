<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Supplier;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class SupplierPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Supplier $supplier): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, Supplier $supplier): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, Supplier $supplier): bool
    {
        return $this->allow($account);
    }
}