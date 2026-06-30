<?php

namespace App\Repositories\Contracts;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupplierRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách nhà cung cấp (có phân trang).
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Tổng số nhà cung cấp.
     */
    public function totalCount(): int;

    /**
     * Số nhà cung cấp đang active.
     */
    public function activeCount(): int;

    /**
     * Tạo mới nhà cung cấp.
     */
    public function create(array $data): Supplier;

    /**
     * Cập nhật nhà cung cấp.
     */
    public function update(Supplier $supplier, array $data): bool;

    /**
     * Xóa nhà cung cấp.
     */
    public function delete(Supplier $supplier): bool;

    /**
     * Kiểm tra nhà cung cấp có phiếu nhập kho liên quan không.
     */
    public function hasStockReceipts(Supplier $supplier): bool;
}
