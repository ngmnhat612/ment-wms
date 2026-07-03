<?php

namespace App\Services\Master;

use App\Models\Master\ReorderRule;
use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\ReorderRuleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReorderRuleService
{
    public function __construct(
        private readonly ReorderRuleRepositoryInterface $reorderRuleRepository,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->reorderRuleRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->reorderRuleRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->reorderRuleRepository->activeCount();
    }

    // ===== WRITE =====

    public function create(array $data): ReorderRule
    {
        $trashed = $this->reorderRuleRepository->findTrashed(
            $data['product_id'],
            $data['warehouse_id']
        );

        if ($trashed) {
            return $this->reorderRuleRepository->restoreAndUpdate($trashed, [
                'employee_id' => $data['employee_id'] ?? null,
                'min_qty'     => $data['min_qty'],
                'max_qty'     => $data['max_qty'],
                'note'        => $data['note'] ?? null,
                'status'      => $data['status'],
            ]);
        }

        return $this->reorderRuleRepository->create($data);
    }

    public function update(ReorderRule $rule, array $data): ReorderRule
    {
        $this->reorderRuleRepository->update($rule, $data);

        return $rule->fresh();
    }

    public function delete(ReorderRule $rule): void
    {
        $this->reorderRuleRepository->delete($rule);
    }

    /**
     * Tạo/cập nhật ReorderRule tự động khi Thêm/Sửa vật tư ở form Sản phẩm.
     * - Chưa có rule (kể cả đã bị xóa mềm) -> tạo mới / restore, mặc định min=0, max=0.
     * - Đã có rule đang active -> chỉ cập nhật min_qty/max_qty, kể cả khi set về 0/0
     *   (KHÔNG xóa rule, vì rule có thể đang giữ employee_id/note đã gán
     *   thủ công ở trang "Gán Min-Max" trước đó).
     *
     * Tái sử dụng create()/update() public để thừa hưởng logic restore-from-trashed,
     * tránh trùng lặp và tránh vi phạm unique(product_id, warehouse_id) khi rule cũ
     * đang ở trạng thái đã xóa mềm.
     */
    public function syncForProduct(int $productId, int $warehouseId, int $minQty, int $maxQty): void
    {
        $existing = $this->reorderRuleRepository->findByProductAndWarehouse($productId, $warehouseId);

        if ($existing) {
            $this->update($existing, [
                'min_qty' => $minQty,
                'max_qty' => $maxQty,
            ]);
            return;
        }

        $this->create([
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'employee_id'  => null,
            'min_qty'      => $minQty,
            'max_qty'      => $maxQty,
            'note'         => null,
            'status'       => \App\Enums\ActiveStatus::Active,
        ]);
    }

    /**
     * Xóa ReorderRule khi vật tư bị xóa.
     */
    public function deleteForProduct(int $productId): void
    {
        $this->reorderRuleRepository->deleteByProduct($productId);
    }

    /**
     * Kho mặc định — hiện dự án chỉ có 1 kho.
     * Cache trong 1 request để tránh query lặp lại khi gọi syncForProduct nhiều lần.
     */
    public function defaultWarehouseId(): int
    {
        static $id = null;

        if ($id === null) {
            $id = Warehouse::query()->value('id')
                ?? throw new \RuntimeException('Chưa có kho nào trong hệ thống.');
        }

        return $id;
    }
}
