<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Warehouse;
use App\Models\Master\WarehouseEmployee;

interface WarehouseEmployeeRepositoryInterface
{
    public function findWarehouseOrFail(int $warehouseId): Warehouse;

    public function create(array $data): WarehouseEmployee;

    public function update(WarehouseEmployee $warehouseEmployee, array $data): WarehouseEmployee;

    public function delete(WarehouseEmployee $warehouseEmployee): bool;

    /**
     * Bỏ is_primary của toàn bộ nhân viên hiện tại trong kho (trước khi gán người mới).
     */
    public function clearPrimaryForWarehouse(int $warehouseId): void;

    /**
     * Bỏ is_primary của toàn bộ nhân viên hiện tại trong kho, ngoại trừ 1 bản ghi
     * (dùng khi update chính bản ghi đó thành is_primary).
     */
    public function clearPrimaryForWarehouseExcept(int $warehouseId, int $exceptId): void;
}