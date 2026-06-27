<?php

namespace App\Repositories\Contracts;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BrandRepositoryInterface
{
    /**
     * Tìm kiếm + lọc danh sách thương hiệu (có phân trang).
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Lấy danh sách thương hiệu đang active (dùng cho dropdown).
     */
    public function allActive(): Collection;

    /**
     * Tạo mới thương hiệu.
     */
    public function create(array $data): Brand;

    /**
     * Cập nhật thương hiệu.
     */
    public function update(Brand $brand, array $data): bool;

    /**
     * Xóa mềm thương hiệu.
     */
    public function delete(Brand $brand): bool;

}