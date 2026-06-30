<?php

namespace App\Repositories\Contracts;

use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LocationRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách vị trí (có phân trang).
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Tổng số vị trí.
     */
    public function totalCount(): int;

    /**
     * Số vị trí đang active.
     */
    public function activeCount(): int;

    /**
     * Số vị trí loại Internal.
     */
    public function internalCount(): int;

    /**
     * Lấy danh sách vị trí Internal đang active (dùng cho dropdown chọn cha).
     */
    public function getParentOptions(): Collection;

    /**
     * Lấy danh sách kho đang active (dùng cho dropdown chọn kho).
     */
    public function getActiveWarehouses(): Collection;

    /**
     * Lấy toàn bộ vị trí để dựng cây (eager-load children).
     */
    public function allForTree(): Collection;

    /**
     * Tạo mới vị trí.
     */
    public function create(array $data): Location;

    /**
     * Cập nhật vị trí.
     */
    public function update(Location $location, array $data): bool;

    /**
     * Xóa vị trí.
     */
    public function delete(Location $location): bool;

    /**
     * Kiểm tra vị trí có con không.
     */
    public function hasChildren(Location $location): bool;

    /**
     * Kiểm tra vị trí có tồn kho không.
     */
    public function hasStock(Location $location): bool;

    /**
     * Kiểm tra vị trí có phải root của kho không.
     */
    public function isRootLocation(Location $location): bool;

    /**
     * Lấy tất cả ID con cháu (đệ quy) — dùng để kiểm tra vòng tròn parent.
     *
     * @return int[]
     */
    public function getDescendantIds(Location $location): array;
}
