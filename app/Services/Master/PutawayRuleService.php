<?php

namespace App\Services\Master;

use App\Models\Master\PutawayRule;
use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\PutawayRuleFormDataRepositoryInterface;
use App\Repositories\Contracts\Master\PutawayRuleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PutawayRuleService
{
    public function __construct(
        private readonly PutawayRuleRepositoryInterface $putawayRuleRepository,
        private readonly PutawayRuleFormDataRepositoryInterface $formDataRepository,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->putawayRuleRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->putawayRuleRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->putawayRuleRepository->activeCount();
    }

    // ===== FORM DATA (dropdown lookups cho index/form) =====

    public function activeProducts(): Collection
    {
        return $this->formDataRepository->activeProducts();
    }

    public function activeCategories(): Collection
    {
        return $this->formDataRepository->activeCategories();
    }

    public function activeInternalLocations(): Collection
    {
        return $this->formDataRepository->activeInternalLocations();
    }

    public function defaultWarehouse(): ?Warehouse
    {
        return $this->formDataRepository->defaultWarehouse();
    }

    // ===== WRITE =====

    public function create(array $data): PutawayRule
    {
        return $this->putawayRuleRepository->create($data);
    }

    public function update(PutawayRule $rule, array $data): PutawayRule
    {
        $this->putawayRuleRepository->update($rule, $data);

        return $rule->fresh();
    }

    public function delete(PutawayRule $rule): void
    {
        $this->putawayRuleRepository->delete($rule);
    }

    /**
     * Tạo/cập nhật PutawayRule (theo product) tự động khi Thêm/Sửa vật tư ở form Sản phẩm.
     */
    public function syncForProduct(int $productId, int $warehouseId, ?int $locationId): void
    {
        $existing = $this->putawayRuleRepository->findByProductAndWarehouse($productId, $warehouseId);

        if ($existing) {
            $this->update($existing, ['location_id' => $locationId]);
            return;
        }

        $this->create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $productId,
            'category_id'  => null,
            'location_id'  => $locationId,
            'note'         => null,
            'status'       => \App\Enums\ActiveStatus::Active,
        ]);
    }

    /**
     * Xóa PutawayRule khi vật tư bị xóa.
     */
    public function deleteForProduct(int $productId): void
    {
        $rule = PutawayRule::where('product_id', $productId)->first();
        if ($rule) {
            $this->delete($rule);
        }
    }
}
