<?php

namespace App\Repositories\Contracts;

use App\Models\Department;
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

}
