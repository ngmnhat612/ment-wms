<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Product;
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
     * Lấy nhiều sản phẩm theo danh sách ID, index theo id.
     * Dùng để validate business (vd: đọc tracking_type) mà không query
     * Model trực tiếp trong FormRequest/Controller.
     */
    public function findManyByIds(array $ids): Collection;

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
     * Kiểm tra barcode đã tồn tại chưa (loại trừ product hiện tại).
     */
    public function barcodeExists(string $barcode, ?int $excludeId = null): bool;

    /**
     * Lấy tất cả vật tư gốc đang active (dùng cho datalist biến thể).
     */
    public function allRootActive(): Collection;

    /**
     * Lấy TOÀN BỘ vật tư (kể cả biến thể, kể cả inactive), sắp theo tên —
     * dùng cho dropdown filter ở màn Tồn kho (không chỉ root/active như
     * allRootActive(), vì tồn kho có thể thuộc bất kỳ vật tư nào).
     */
    public function allOrdered(): Collection;

    /**
     * Kiểm tra mã MenT (code) đã tồn tại chưa (loại trừ product hiện tại nếu đang sửa).
     * Dùng cho validate AJAX (blur) ở form Thêm/Sửa vật tư & biến thể, khớp với
     * rule 'code' => Rule::unique('products', 'code') trong Store/UpdateProductRequest.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;

    /**
     * Tìm vật tư gốc theo code.
     */
    public function findRootByCode(string $code): ?Product;

}
