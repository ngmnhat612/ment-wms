<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Collection;

interface PutawayRuleFormDataRepositoryInterface
{
    /**
     * Vật tư đang active — dùng cho dropdown "Sản phẩm" trong form.
     */
    public function activeProducts(): Collection;

    /**
     * Danh mục đang active — dùng cho dropdown "Danh mục" trong form.
     */
    public function activeCategories(): Collection;

    /**
     * Vị trí Internal đang active — dùng cho dropdown "Vị trí đích".
     */
    public function activeInternalLocations(): Collection;

    /**
     * Kho mặc định (hệ thống chỉ gán 1 kho duy nhất).
     */
    public function defaultWarehouse(): ?Warehouse;
}