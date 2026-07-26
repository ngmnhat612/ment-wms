<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách bộ phận (có phân trang).
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy danh sách bộ phận đang active (dùng cho dropdown).
     */
    public function allActive(): Collection;

    /**
     * Lấy TẤT CẢ bộ phận (kể cả Ngừng hoạt động) — dùng cho dropdown ở chế độ
     * chỉnh sửa Employee, để Bộ phận đã inactive nhưng đang được nhân viên tham
     * chiếu vẫn hiển thị đúng tên.
     */
    public function allOrdered(): Collection;

    /**
     * Tạo mới bộ phận.
     */
    public function create(array $data): Department;

    /**
     * Cập nhật bộ phận.
     */
    public function update(Department $department, array $data): bool;

    /**
     * Xóa mềm bộ phận.
     */
    public function delete(Department $department): bool;

    /**
     * Kiểm tra mã đã tồn tại chưa — dùng cho validate AJAX (blur) ở form Thêm.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool;
}
