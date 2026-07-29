<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Collection;

interface ReorderRuleFormDataRepositoryInterface
{
    /**
     * Vật tư đang active — dùng cho dropdown "Vật tư" khi TẠO MỚI.
     */
    public function activeProducts(): Collection;

    /**
     * TẤT CẢ vật tư (kể cả Ngưng hoạt động) — dùng cho dropdown "Vật tư"
     * khi CHỈNH SỬA, để rule cũ vẫn hiển thị đúng tên vật tư dù đã ngưng hoạt động.
     */
    public function allProducts(): Collection;

    /**
     * Nhân viên đang active, loại trừ tài khoản Admin — dùng cho dropdown
     * "Người phụ trách" khi TẠO MỚI.
     */
    public function activeNonAdminEmployees(): Collection;

    /**
     * TẤT CẢ nhân viên (kể cả Ngưng hoạt động), loại trừ tài khoản Admin —
     * dùng cho dropdown "Người phụ trách" khi CHỈNH SỬA.
     */
    public function allNonAdminEmployees(): Collection;

    /**
     * Kho mặc định (hệ thống chỉ gán 1 kho duy nhất).
     */
    public function defaultWarehouse(): ?Warehouse;
}
