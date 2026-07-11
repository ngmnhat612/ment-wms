<?php

namespace App\Repositories\Eloquent\Stocktake;

use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\InventoryFreeze;
use App\Repositories\Contracts\Stocktake\InventoryFreezeRepositoryInterface;

class InventoryFreezeRepository implements InventoryFreezeRepositoryInterface
{
    public function create(array $headerData): InventoryFreeze
    {
        return InventoryFreeze::create($headerData);
    }

    public function unfreeze(InventoryFreeze $freeze): InventoryFreeze
    {
        $freeze->update(['unfrozen_at' => now()]);

        return $freeze->fresh();
    }

    public function hasActiveFreeze(InventoryCheck $inventoryCheck): bool
    {
        return $inventoryCheck->freezes()->whereNull('unfrozen_at')->exists();
    }
}
