<?php

namespace App\Repositories\Eloquent\Master;

use App\Models\Master\Account;
use App\Repositories\Contracts\Master\AccountRepositoryInterface;

class AccountRepository implements AccountRepositoryInterface
{
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    public function update(Account $account, array $data): bool
    {
        return $account->update($data);
    }

    public function delete(Account $account): bool
    {
        return $account->delete();
    }
}
