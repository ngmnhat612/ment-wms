<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách danh mục (có phân trang).
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Tổng số danh mục.
     */
    public function totalCount(): int;

    /**
     * Số danh mục đang active.
     */
    public function activeCount(): int;

    /**
     * Lấy danh sách danh mục đang active (dùng cho dropdown).
     */
    public function allActive(): Collection;

    /**
     * Lấy TOÀN BỘ danh mục (kể cả inactive), sắp theo tên — dùng cho
     * dropdown filter ở các màn hình chỉ cần lọc, không ràng buộc trạng thái
     * (vd: màn Tồn kho — tồn cũ có thể thuộc danh mục đã inactive).
     */
    public function allOrdered(): Collection;

    /**
     * Lấy danh sách danh mục cha hợp lệ (active, dùng cho select parent).
     */
    public function getParentOptions(): Collection;

    /**
     * Tạo mới danh mục.
     */
    public function create(array $data): Category;

    /**
     * Cập nhật danh mục.
     */
    public function update(Category $category, array $data): bool;

    /**
     * Xóa danh mục.
     */
    public function delete(Category $category): bool;

    /**
     * Kiểm tra danh mục có danh mục con không.
     */
    public function hasChildren(Category $category): bool;

    /**
     * Lấy tất cả ID con cháu (đệ quy) — dùng để kiểm tra vòng tròn parent.
     *
     * @return int[]
     */
    public function getDescendantIds(Category $category): array;
}