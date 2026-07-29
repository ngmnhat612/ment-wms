<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Uom;
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
     * Lấy TẤT CẢ đơn vị tính (kể cả Ngưng hoạt động) — dùng cho dropdown ở chế
     * độ chỉnh sửa Product, để ĐVT đã inactive nhưng đang được product tham
     * chiếu vẫn hiển thị đúng tên.
     */
    public function allOrdered(): Collection;

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
     * Kiểm tra đơn vị tính đang được dùng trong quy đổi.
     */
    public function hasConversions(Uom $uom): bool;

    /**
     * Kiểm tra mã đã tồn tại chưa — dùng cho validate AJAX (blur) ở form Thêm.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;
}
