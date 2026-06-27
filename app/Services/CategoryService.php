<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CodeGeneratorService        $codeGeneratorService,
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
     * Lấy danh sách danh mục cha cho select trong modal.
     */
    public function getParentOptions(): Collection
    {
        return $this->categoryRepository->getParentOptions();
    }

    // ===== WRITE =====

    /**
     * Tạo mới danh mục.
     */
    public function create(array $data): Category
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('categories', 'code', 'DM', 4);

        return $this->categoryRepository->create([
            'code'      => $code,
            'name'      => $data['name'],
            'note'      => $data['note'] ?? null,
            'status'    => $data['status'],
        ]);
    }

    /**
     * Cập nhật danh mục.
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

        return $category->fresh();
    }

    /**
     * Xóa danh mục.
     *
     * @throws \RuntimeException khi có danh mục con hoặc vật tư đang dùng.
     */
    public function delete(Category $category): void
    {
        if ($this->categoryRepository->hasProducts($category)) {
            throw new \RuntimeException(
                'Không thể xóa nếu đã gán danh mục vật tư.'
            );
        }

        $this->categoryRepository->delete($category);
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
}