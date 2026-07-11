<?php

namespace App\Repositories\Contracts\Stocktake;

use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\InventoryFreeze;

interface InventoryFreezeRepositoryInterface
{
    public function create(array $headerData): InventoryFreeze;

    public function unfreeze(InventoryFreeze $freeze): InventoryFreeze;

    public function hasActiveFreeze(InventoryCheck $inventoryCheck): bool;
}
