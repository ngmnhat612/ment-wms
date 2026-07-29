<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Collection;

interface PutawayRuleFormDataRepositoryInterface
{
    /**
     * Vật tư đang active — dùng cho dropdown "Vật tư" khi TẠO MỚI.
     */
    public function activeProducts(): Collection;

    /**
     * TẤT CẢ vật tư (kể cả Ngưng hoạt động) — dùng cho dropdown "Vật tư" khi
     * CHỈNH SỬA, để rule cũ vẫn hiển thị đúng tên dù vật tư đã ngưng hoạt động.
     */
    public function allProducts(): Collection;

    /**
     * Danh mục đang active — dùng cho dropdown "Danh mục" khi TẠO MỚI.
     */
    public function activeCategories(): Collection;

    /**
     * TẤT CẢ danh mục (kể cả Ngưng hoạt động) — dùng cho dropdown "Danh mục"
     * khi CHỈNH SỬA.
     */
    public function allCategories(): Collection;

    /**
     * Vị trí Internal đang active — dùng cho dropdown "Vị trí đích" khi TẠO MỚI.
     */
    public function activeInternalLocations(): Collection;

    /**
     * TẤT CẢ vị trí Internal (kể cả Ngưng hoạt động) — dùng cho dropdown
     * "Vị trí đích" khi CHỈNH SỬA.
     */
    public function allInternalLocations(): Collection;

    /**
     * Kho mặc định (hệ thống chỉ gán 1 kho duy nhất).
     */
    public function defaultWarehouse(): ?Warehouse;
}
