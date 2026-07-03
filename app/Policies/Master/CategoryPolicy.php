<?php

namespace App\Policies\Master;

use App\Models\Master\Account;
use App\Models\Master\Category;
use App\Policies\Concerns\HasRoleDepartmentAuthorization;

class CategoryPolicy
{
    use HasRoleDepartmentAuthorization;

    public function viewAny(Account $account): bool
    {
        return true;
    }

    public function view(Account $account, Category $category): bool
    {
        return true;
    }

    public function create(Account $account): bool
    {
        return $this->allow($account);
    }

    public function update(Account $account, Category $category): bool
    {
        return $this->allow($account);
    }

    public function delete(Account $account, Category $category): bool
    {
        return $this->allow($account);
    }
}