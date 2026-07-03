<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\Product;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class ProductPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Product $product): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, Product $product): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, Product $product): bool
    {
        return $this->allow($account);
    }
}