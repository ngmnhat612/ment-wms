<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\PutawayRule;

class PutawayRulePolicy
{
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
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    public function update(Account $account, PutawayRule $rule): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }

    public function delete(Account $account, PutawayRule $rule): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed('Kho');
    }
}