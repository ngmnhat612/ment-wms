<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Sn;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class SnPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Sn $sn): bool
    {
        return true;
    }

    /**
     * NOTE: điều chỉnh department mặc định ('Kho') nếu dự án (Sn) thuộc bộ phận khác.
     */
    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, Sn $sn): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, Sn $sn): bool
    {
        return $this->allow($account);
    }
}