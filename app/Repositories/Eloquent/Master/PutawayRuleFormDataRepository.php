<?php

namespace App\Repositories\Eloquent\Master;

use App\Models\Master\Category;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\PutawayRuleFormDataRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PutawayRuleFormDataRepository implements PutawayRuleFormDataRepositoryInterface
{
    public function activeProducts(): Collection
    {
        return Product::where('status', 1)->orderBy('code')->get(['id', 'code', 'name']);
    }

    public function allProducts(): Collection
    {
        return Product::orderBy('code')->get(['id', 'code', 'name', 'status']);
    }

    public function activeCategories(): Collection
    {
        return Category::where('status', 1)->orderBy('name')->get(['id', 'name']);
    }

    public function allCategories(): Collection
    {
        return Category::orderBy('name')->get(['id', 'name', 'status']);
    }

    public function activeInternalLocations(): Collection
    {
        return Location::where('status', 1)
            ->internal()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    public function allInternalLocations(): Collection
    {
        return Location::internal()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'status']);
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return Warehouse::where('status', 1)->orderBy('id')->first(['id', 'code', 'name']);
    }
}
