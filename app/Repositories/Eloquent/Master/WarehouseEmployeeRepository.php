<?php

namespace App\Repositories\Eloquent\Master;

use App\Models\Master\Warehouse;
use App\Models\Master\WarehouseEmployee;
use App\Repositories\Contracts\Master\WarehouseEmployeeRepositoryInterface;

class WarehouseEmployeeRepository implements WarehouseEmployeeRepositoryInterface
{
    public function findWarehouseOrFail(int $warehouseId): Warehouse
    {
        return Warehouse::findOrFail($warehouseId);
    }

    public function create(array $data): WarehouseEmployee
    {
        return WarehouseEmployee::create($data);
    }

    public function update(WarehouseEmployee $warehouseEmployee, array $data): WarehouseEmployee
    {
        $warehouseEmployee->update($data);

        return $warehouseEmployee->fresh();
    }

    public function delete(WarehouseEmployee $warehouseEmployee): bool
    {
        return (bool) $warehouseEmployee->delete();
    }

    public function clearPrimaryForWarehouse(int $warehouseId): void
    {
        WarehouseEmployee::where('warehouse_id', $warehouseId)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    public function clearPrimaryForWarehouseExcept(int $warehouseId, int $exceptId): void
    {
        WarehouseEmployee::where('warehouse_id', $warehouseId)
            ->where('is_primary', true)
            ->where('id', '!=', $exceptId)
            ->update(['is_primary' => false]);
    }
}