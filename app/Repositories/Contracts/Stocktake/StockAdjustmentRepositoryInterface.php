<?php

namespace App\Repositories\Contracts\Stocktake;

use App\Models\Stocktake\InventoryCheckDetail;
use App\Models\Stocktake\StockAdjustment;

interface StockAdjustmentRepositoryInterface
{
    public function findWithDetails(int $id): ?StockAdjustment;

    public function create(array $headerData): StockAdjustment;

    /**
     * Copy dữ liệu từ các InventoryCheckDetail đã chọn (đã có chênh lệch)
     * sang StockAdjustmentDetail, giữ liên kết check_detail_id.
     *
     * @param \Illuminate\Support\Collection<int, InventoryCheckDetail> $checkDetails
     */
    public function createDetailsFromCheckDetails(StockAdjustment $adjustment, $checkDetails): void;

    public function generateCode(): string;
}
