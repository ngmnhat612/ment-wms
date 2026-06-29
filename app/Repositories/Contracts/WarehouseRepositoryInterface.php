<?php

namespace App\Repositories\Contracts;

use App\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách kho (có phân trang).
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Tổng số kho.
     */
    public function totalCount(): int;

    /**
     * Số kho đang active.
     */
    public function activeCount(): int;

    /**
     * Lấy danh sách kho đang active (dùng cho dropdown).
     */
    public function allActive(): Collection;

    /**
     * Tạo mới kho.
     */
    public function create(array $data): Warehouse;

    /**
     * Cập nhật kho.
     */
    public function update(Warehouse $warehouse, array $data): bool;

    /**
     * Xóa kho.
     */
    public function delete(Warehouse $warehouse): bool;

    /**
     * Kiểm tra kho có tồn kho nội bộ hay không.
     */
    public function hasStock(Warehouse $warehouse): bool;
}