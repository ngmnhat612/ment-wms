<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách nhân viên (có phân trang).
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy danh sách nhân viên đang active (dùng cho dropdown).
     */
    public function allActive(): Collection;

    /**
     * Đếm tổng số nhân viên.
     */
    public function countAll(): int;

    /**
     * Đếm số nhân viên đang active.
     */
    public function countActive(): int;

    /**
     * Đếm số nhân viên đã có tài khoản đăng nhập.
     */
    public function countWithAccount(): int;

    /**
     * Tạo mới nhân viên.
     */
    public function create(array $data): Employee;

    /**
     * Cập nhật nhân viên.
     */
    public function update(Employee $employee, array $data): bool;

    /**
     * Xóa mềm nhân viên.
     */
    public function delete(Employee $employee): bool;

}
