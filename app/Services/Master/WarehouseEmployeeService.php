<?php

namespace App\Services\Master;

use App\Models\Master\WarehouseEmployee;
use App\Repositories\Contracts\Master\WarehouseEmployeeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class WarehouseEmployeeService
{
    public function __construct(
        private readonly WarehouseEmployeeRepositoryInterface $warehouseEmployeeRepository,
    ) {}

    /**
     * Gán nhân viên vào kho.
     * Nếu is_primary = true → bỏ is_primary của người cũ trong kho đó.
     */
    public function assign(array $data): WarehouseEmployee
    {
        // Đảm bảo warehouse tồn tại trước khi gán.
        $this->warehouseEmployeeRepository->findWarehouseOrFail($data['warehouse_id']);

        return DB::transaction(function () use ($data) {
            if (! empty($data['is_primary'])) {
                $this->warehouseEmployeeRepository->clearPrimaryForWarehouse($data['warehouse_id']);
            }

            return $this->warehouseEmployeeRepository->create([
                'warehouse_id' => $data['warehouse_id'],
                'employee_id'  => $data['employee_id'],
                'is_primary'   => (bool) ($data['is_primary'] ?? false),
            ]);
        });
    }

    /**
     * Cập nhật is_primary cho một phân công kho.
     */
    public function updatePrimary(WarehouseEmployee $warehouseEmployee, array $data): WarehouseEmployee
    {
        $isPrimary = (bool) ($data['is_primary'] ?? false);

        return DB::transaction(function () use ($warehouseEmployee, $isPrimary) {
            if ($isPrimary) {
                $this->warehouseEmployeeRepository->clearPrimaryForWarehouseExcept(
                    $warehouseEmployee->warehouse_id,
                    $warehouseEmployee->id,
                );
            }

            return $this->warehouseEmployeeRepository->update($warehouseEmployee, [
                'is_primary' => $isPrimary,
            ]);
        });
    }

    /**
     * Hủy gán nhân viên khỏi kho.
     */
    public function unassign(WarehouseEmployee $warehouseEmployee): bool
    {
        return $this->warehouseEmployeeRepository->delete($warehouseEmployee);
    }
}