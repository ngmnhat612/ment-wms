<?php

namespace App\Services\Master;

use App\Models\Master\Category;
use App\Models\Master\Warehouse;
use App\Repositories\Contracts\Master\CategoryRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Concerns\CodeGeneratorService;

class CategoryService
{
    use ChecksForeignKeyUsage;

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CodeGeneratorService        $codeGeneratorService,
        private readonly PutawayRuleService          $putawayRuleService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->categoryRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->categoryRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->categoryRepository->activeCount();
    }

    /**
     * Lấy danh mục active — dùng cho dropdown trong các module khác (Product…).
     */
    public function getActive(): Collection
    {
        return $this->categoryRepository->allActive();
    }

    /**
     * Lấy TẤT CẢ danh mục (kể cả Ngưng hoạt động) — dùng cho dropdown ở chế độ
     * chỉnh sửa Product, để category đã inactive nhưng đang được product tham
     * chiếu vẫn hiển thị đúng tên thay vì bị mất do không có trong option active.
     */
    public function getAllIncludingInactive(): Collection
    {
        return $this->categoryRepository->allOrdered();
    }

    /**
     * Lấy danh sách danh mục cha cho select trong modal.
     */
    public function getParentOptions(): Collection
    {
        return $this->categoryRepository->getParentOptions();
    }

    /**
     * Vị trí Internal đang active — dùng cho dropdown "Gợi ý vị trí" ở form
     * Danh mục (đồng bộ với PutawayRule khi tạo/sửa danh mục). Tái sử dụng
     * PutawayRuleService vì đã có sẵn dependency và cùng nguồn dữ liệu.
     */
    public function activeInternalLocations(): Collection
    {
        return $this->putawayRuleService->activeInternalLocations();
    }

    // ===== WRITE =====

    /**
     * Tạo mới danh mục.
     * Tự động tạo/cập nhật kèm PutawayRule (vị trí gợi ý gán theo danh mục)
     * nếu có gửi location_id.
     */
    public function create(array $data): Category
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('categories', 'code', 'DM', 4);

        $category = $this->categoryRepository->create([
            'code'      => $code,
            'name'      => $data['name'],
            'note'      => $data['note'] ?? null,
            'status'    => $data['status'],
        ]);

        $this->putawayRuleService->syncForCategory(
            $category->id,
            $this->defaultWarehouseId(),
            isset($data['location_id']) ? (int) $data['location_id'] : null,
        );

        return $category;
    }

    /**
     * Cập nhật danh mục.
     * Đồng bộ PutawayRule theo location_id gửi lên (kể cả khi về null,
     * không xóa rule — chỉ gỡ vị trí gán, giống ProductService::update()).
     *
     * @throws \RuntimeException khi chọn danh mục con làm cha (vòng tròn).
     */
    public function update(Category $category, array $data): Category
    {
        $this->categoryRepository->update($category, [
            'code'      => strtoupper(trim($data['code'])),
            'name'      => $data['name'],
            'note'      => $data['note'] ?? null,
            'status'    => $data['status'],
        ]);

        $this->putawayRuleService->syncForCategory(
            $category->id,
            $this->defaultWarehouseId(),
            isset($data['location_id']) ? (int) $data['location_id'] : null,
        );

        return $category->fresh();
    }

    /**
     * Xóa cứng danh mục.
     * Xóa PutawayRule tương ứng trước khi xóa danh mục (giống ProductService::delete()),
     * để guardNotInUse() không chặn nhầm do putaway_rules.category_id đang tham chiếu.
     *
     * @throws \RuntimeException khi có danh mục con, hoặc đang được tham chiếu
     *         bởi bất kỳ bảng nào khác (tự động phát hiện qua ràng buộc khóa ngoại).
     */
    public function delete(Category $category): void
    {
        $this->putawayRuleService->deleteForCategory($category->id);

        $this->guardNotInUse('categories', 'id', $category->id, 'Danh mục', $category->name);

        $this->categoryRepository->delete($category);
    }

    /**
     * Kiểm tra mã danh mục đã tồn tại chưa — dùng cho validate AJAX (blur) ở
     * form Thêm/Sửa danh mục. Chuẩn hoá code giống lúc lưu (create()) để so
     * sánh nhất quán (uppercase, trim).
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->categoryRepository->codeExists(
            strtoupper(trim($code)),
            $excludeId
        );
    }

    // ===== PRIVATE HELPERS =====

    /**
     * Ngăn chọn danh mục con / chính nó làm cha (vòng tròn).
     *
     * @throws \RuntimeException
     */
    private function guardCircularParent(Category $category, int $newParentId): void
    {
        if ($newParentId === $category->id) {
            throw new \RuntimeException(
                'Không thể chọn chính danh mục này làm danh mục cha.'
            );
        }

        $descendantIds = $this->categoryRepository->getDescendantIds($category);

        if (in_array($newParentId, $descendantIds)) {
            throw new \RuntimeException(
                'Không thể chọn danh mục con làm danh mục cha.'
            );
        }
    }

    /**
     * Kho mặc định — hiện dự án chỉ có 1 kho. Cache trong 1 request để
     * tránh query lặp lại khi gọi syncForCategory nhiều lần (giống
     * ReorderRuleService::defaultWarehouseId()).
     */
    private function defaultWarehouseId(): int
    {
        static $id = null;

        if ($id === null) {
            $id = Warehouse::query()->value('id')
                ?? throw new \RuntimeException('Chưa có kho nào trong hệ thống.');
        }

        return $id;
    }
}
