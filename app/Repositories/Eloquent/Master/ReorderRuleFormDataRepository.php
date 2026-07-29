<?php

namespace App\Repositories\Eloquent\Master;

use App\Models\Master\Employee;
use App\Models\Master\Product;
use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\ReorderRuleFormDataRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReorderRuleFormDataRepository implements ReorderRuleFormDataRepositoryInterface
{
    public function activeProducts(): Collection
    {
        return Product::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function allProducts(): Collection
    {
        return Product::orderBy('code')->get(['id', 'code', 'name', 'status']);
    }

    public function activeNonAdminEmployees(): Collection
    {
        return Employee::where('status', 1)
            ->whereDoesntHave('account', function ($q) {
                $q->role('Admin');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    public function allNonAdminEmployees(): Collection
    {
        return Employee::whereDoesntHave('account', function ($q) {
                $q->role('Admin');
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'status']);
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return Warehouse::where('status', 1)->orderBy('id')->first(['id', 'code', 'name']);
    }
}

