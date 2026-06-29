<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly CodeGeneratorService         $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->warehouseRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->warehouseRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->warehouseRepository->activeCount();
    }

    /**
     * Lấy kho active — dùng cho dropdown trong các module khác.
     */
    public function getActive(): Collection
    {
        return $this->warehouseRepository->allActive();
    }

    // ===== WRITE =====

    /**
     * Tạo mới kho và tự động sinh vị trí ảo root.
     */
    public function create(array $data): Warehouse
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('warehouses', 'code', 'K', 4);

        return DB::transaction(function () use ($data, $code) {
            $warehouse = $this->warehouseRepository->create([
                'code'       => $code,
                'name'       => $data['name'],
                'manager_id' => $data['manager_id'],
                'phone'      => $data['phone'] ?? null,
                'address'    => $data['address'] ?? null,
                'note'       => $data['note'] ?? null,
                'status'     => $data['status'],
            ]);

            // Tự động tạo vị trí ảo root cho kho
            $rootLocation = $warehouse->locations()->create([
                'code'   => 'VIR-' . $warehouse->code,
                'name'   => 'Vị trí ảo — ' . $warehouse->name,
                'type'   => 2, // Virtual
                'status' => 1,
            ]);

            $warehouse->update(['root_location_id' => $rootLocation->id]);

            return $warehouse->fresh();
        });
    }

    /**
     * Cập nhật kho.
     */
    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $this->warehouseRepository->update($warehouse, [
            'name'       => $data['name'],
            'manager_id' => $data['manager_id'],
            'phone'      => $data['phone'] ?? null,
            'address'    => $data['address'] ?? null,
            'note'       => $data['note'] ?? null,
            'status'     => $data['status'],
        ]);

        return $warehouse->fresh();
    }

    /**
     * Xóa kho.
     *
     * @throws \RuntimeException khi kho còn tồn kho.
     */
    public function delete(Warehouse $warehouse): void
    {
        if ($this->warehouseRepository->hasStock($warehouse)) {
            throw new \RuntimeException(
                "Không thể xóa kho \"{$warehouse->name}\" vì đang có tồn kho."
            );
        }

        $this->warehouseRepository->delete($warehouse);
    }
}