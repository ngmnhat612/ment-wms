<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\PutawayRule;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class PutawayRulePolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, PutawayRule $rule): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, PutawayRule $rule): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, PutawayRule $rule): bool
    {
        return $this->allow($account);
    }
}