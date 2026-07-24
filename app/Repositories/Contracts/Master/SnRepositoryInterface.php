<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Sn;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SnRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách dự án (có phân trang).
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy danh sách dự án đang active (dùng cho dropdown).
     */
    public function allActive(): Collection;

    /**
     * Tạo mới dự án.
     */
    public function create(array $data): Sn;

    /**
     * Cập nhật dự án.
     */
    public function update(Sn $sn, array $data): bool;

    /**
     * Xóa mềm dự án.
     */
    public function delete(Sn $sn): bool;

    /**
     * Kiểm tra mã đã tồn tại chưa — dùng cho validate AJAX (blur) ở form Thêm/Sửa.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;
}
