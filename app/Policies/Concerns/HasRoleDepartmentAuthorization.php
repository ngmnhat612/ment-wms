<?php

namespace App\Policies\Concerns;

use App\Models\Master\Account;

trait HasRoleDepartmentAuthorization
{
    /**
     * Admin luôn được phép — trừ khi Policy override lại (vd: AccountPolicy).
     */
    protected function allow(Account $account, string $departmentName = 'Kho'): bool
    {
        if ($account->hasRole('Admin')) {
            return true;
        }

        return $account->hasRole('Quản lý')
            && $account->isInDepartmentNamed($departmentName);
    }
}