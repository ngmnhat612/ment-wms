<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách sản phẩm (có phân trang).
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Tổng số sản phẩm.
     */
    public function totalCount(): int;

    /**
     * Số sản phẩm đang active.
     */
    public function activeCount(): int;

    /**
     * Tìm sản phẩm theo ID (eager load quan hệ nếu cần).
     */
    public function findById(int $id, array $with = []): ?Product;

    /**
     * Tạo mới sản phẩm.
     */
    public function create(array $data): Product;

    /**
     * Cập nhật sản phẩm.
     */
    public function update(Product $product, array $data): bool;

    /**
     * Xóa sản phẩm.
     */
    public function delete(Product $product): bool;

    /**
     * Kiểm tra sản phẩm có tồn kho hay không.
     */
    public function hasStock(Product $product): bool;

    /**
     * Kiểm tra barcode đã tồn tại chưa (loại trừ product hiện tại).
     */
    public function barcodeExists(string $barcode, ?int $excludeId = null): bool;

    /**
     * Lấy tất cả vật tư gốc đang active (dùng cho datalist biến thể).
     */
    public function allRootActive(): Collection;

    /**
     * Tìm vật tư gốc theo code.
     */
    public function findRootByCode(string $code): ?Product;
}