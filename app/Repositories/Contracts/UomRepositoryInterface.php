<?php

namespace App\Repositories\Contracts;

use App\Models\Uom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UomRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách đơn vị tính (có phân trang).
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Tổng số đơn vị tính.
     */
    public function totalCount(): int;

    /**
     * Số đơn vị tính đang active.
     */
    public function activeCount(): int;

    /**
     * Lấy danh sách đơn vị tính đang active (dùng cho dropdown).
     */
    public function allActive(): Collection;

    /**
     * Tạo mới đơn vị tính.
     */
    public function create(array $data): Uom;

    /**
     * Cập nhật đơn vị tính.
     */
    public function update(Uom $uom, array $data): bool;

    /**
     * Xóa đơn vị tính.
     */
    public function delete(Uom $uom): bool;

    /**
     * Kiểm tra đơn vị tính đang được gán cho vật tư.
     */
    public function hasProducts(Uom $uom): bool;

    /**
     * Kiểm tra đơn vị tính đang được dùng trong quy đổi.
     */
    public function hasConversions(Uom $uom): bool;
}