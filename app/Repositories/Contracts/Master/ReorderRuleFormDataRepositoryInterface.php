<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Collection;

interface ReorderRuleFormDataRepositoryInterface
{
    /**
     * Vật tư đang active — dùng cho dropdown "Sản phẩm" trong form.
     */
    public function activeProducts(): Collection;

    /**
     * Nhân viên đang active, loại trừ tài khoản Admin — dùng cho dropdown "Người phụ trách".
     */
    public function activeNonAdminEmployees(): Collection;

    /**
     * Kho mặc định (hệ thống chỉ gán 1 kho duy nhất).
     */
    public function defaultWarehouse(): ?Warehouse;
}