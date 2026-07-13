<?php

namespace App\Services\Master;

use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\EmployeeRepositoryInterface;
use App\Repositories\Contracts\Master\WarehouseRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Concerns\CodeGeneratorService;

class WarehouseService
{
    use ChecksForeignKeyUsage;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository,
        private readonly EmployeeRepositoryInterface   $employeeRepository,
        private readonly CodeGeneratorService          $codeGeneratorService,
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

    /**
     * Nhân viên active — dùng cho dropdown "Quản lý kho" / gán nhân viên vào kho.
     */
    public function activeEmployees(): Collection
    {
        return $this->employeeRepository->allActive();
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
                'name'   => $warehouse->name,
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
     * Xóa cứng kho.
     *
     * @throws \RuntimeException khi có kho con, hoặc đang được tham chiếu
     *         bởi bất kỳ bảng nào khác (bao gồm cả tồn kho — stocks.warehouse_id
     *         có khai báo khóa ngoại nên được tự động phát hiện qua guardNotInUse,
     *         không cần kiểm tra riêng).
     *
     * Lưu ý: kho luôn có ít nhất 1 Location root (tạo tự động lúc create()).
     * guardNotInUse sẽ luôn chặn xóa vì locations.warehouse_id còn bản ghi đó
     * — đây là hành vi ĐÚNG, không phải lỗi: phải xóa Location root trước
     * (qua LocationService, nếu location đó không còn ràng buộc gì khác) thì
     * mới xóa được Warehouse.
     */
    public function delete(Warehouse $warehouse): void
    {
        if ($warehouse->hasChildren()) {
            throw new \RuntimeException(
                "Không thể xóa kho \"{$warehouse->name}\" vì có kho con."
            );
        }

        $this->guardNotInUse('warehouses', 'id', $warehouse->id, 'Kho', $warehouse->name);

        $this->warehouseRepository->delete($warehouse);
    }
}