<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\ReorderRule;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class ReorderRulePolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, ReorderRule $rule): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, ReorderRule $rule): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, ReorderRule $rule): bool
    {
        return $this->allow($account);
    }
}