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

    /**
     * TẤT CẢ vật tư (kể cả Ngưng hoạt động) — dùng khi CHỈNH SỬA rule đã có,
     * tránh mất lựa chọn nếu vật tư đó đã bị Ngưng hoạt động.
     */
    public function allProducts(): Collection
    {
        return $this->formDataRepository->allProducts();
    }

    public function activeCategories(): Collection
    {
        return $this->formDataRepository->activeCategories();
    }

    /**
     * TẤT CẢ danh mục (kể cả Ngưng hoạt động) — dùng khi CHỈNH SỬA rule đã có.
     */
    public function allCategories(): Collection
    {
        return $this->formDataRepository->allCategories();
    }

    public function activeInternalLocations(): Collection
    {
        return $this->formDataRepository->activeInternalLocations();
    }

    /**
     * TẤT CẢ vị trí Internal (kể cả Ngưng hoạt động) — dùng khi CHỈNH SỬA rule
     * đã có.
     */
    public function allInternalLocations(): Collection
    {
        return $this->formDataRepository->allInternalLocations();
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
     * Đồng bộ PutawayRule (theo product) khi TẠO MỚI vật tư (create/createVariant).
     *
     * Khác với syncForProduct(): nếu người dùng KHÔNG gán vị trí ($locationId
     * === null) VÀ danh mục của vật tư ĐÃ có vị trí gán sẵn (qua PutawayRule
     * theo category_id), thì KHÔNG tạo rule riêng cho product — vật tư này sẽ
     * kế thừa ngầm vị trí của danh mục (đọc lại qua PutawayRule.category tại
     * thời điểm hiển thị), tránh sinh thêm 1 dòng "Theo vật tư" vô nghĩa
     * (location_id = null) trong danh sách master/putaway-rule.
     *
     * Mọi trường hợp khác (có gán vị trí riêng, hoặc danh mục chưa có vị trí)
     * vẫn tạo rule theo product như syncForProduct() bình thường.
     */
    public function syncForNewProduct(int $productId, int $categoryId, int $warehouseId, ?int $locationId): void
    {
        if ($locationId === null) {
            $categoryRule = $this->putawayRuleRepository->findByCategoryAndWarehouse($categoryId, $warehouseId);

            if ($categoryRule && $categoryRule->location_id !== null) {
                // Danh mục đã có vị trí -> không tạo rule riêng cho product,
                // để product kế thừa ngầm vị trí của danh mục.
                return;
            }
        }

        $this->syncForProduct($productId, $warehouseId, $locationId);
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

    /**
     * Tạo/cập nhật PutawayRule (theo category) tự động khi Thêm/Sửa danh mục
     * ở form Danh mục vật tư. Cùng cơ chế với syncForProduct(), chỉ khác cột
     * mục tiêu là category_id thay vì product_id (2 cột này loại trừ lẫn nhau
     * theo ràng buộc CHECK ở bảng putaway_rules).
     */
    public function syncForCategory(int $categoryId, int $warehouseId, ?int $locationId): void
    {
        $existing = $this->putawayRuleRepository->findByCategoryAndWarehouse($categoryId, $warehouseId);

        if ($existing) {
            $this->update($existing, ['location_id' => $locationId]);
            return;
        }

        $this->create([
            'warehouse_id' => $warehouseId,
            'product_id'   => null,
            'category_id'  => $categoryId,
            'location_id'  => $locationId,
            'note'         => null,
            'status'       => \App\Enums\ActiveStatus::Active,
        ]);
    }

    /**
     * Đồng bộ PutawayRule (theo category) khi TẠO MỚI danh mục.
     *
     * Khác với syncForCategory(): nếu người dùng KHÔNG gán vị trí
     * ($locationId === null), thì KHÔNG tạo rule — tránh sinh thêm 1 dòng
     * "Theo danh mục" vô nghĩa (location_id = null) trong danh sách
     * master/putaway-rule. Rule sẽ chỉ được tạo khi thực sự có vị trí gán.
     *
     * Mọi trường hợp có gán vị trí vẫn tạo rule như syncForCategory() bình thường.
     */
    public function syncForNewCategory(int $categoryId, int $warehouseId, ?int $locationId): void
    {
        if ($locationId === null) {
            return;
        }

        $this->syncForCategory($categoryId, $warehouseId, $locationId);
    }

    /**
     * Xóa PutawayRule khi danh mục bị xóa.
     */
    public function deleteForCategory(int $categoryId): void
    {
        $rule = PutawayRule::where('category_id', $categoryId)->first();
        if ($rule) {
            $this->delete($rule);
        }
    }
}
