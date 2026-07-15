<?php

namespace App\Repositories\Contracts\Stocktake;

use App\Models\Stocktake\InventoryCheckDetail;
use App\Models\Stocktake\StockAdjustment;

interface StockAdjustmentRepositoryInterface
{
    public function findWithDetails(int $id): ?StockAdjustment;

    public function create(array $headerData): StockAdjustment;

    public function createDetailsFromCheckDetails(StockAdjustment $adjustment, $checkDetails): void;

    /**
     * Xóa toàn bộ StockAdjustmentDetail hiện có của phiếu, dùng khi
     * cập nhật lại danh sách dòng chênh lệch (edit).
     */
    public function deleteDetails(StockAdjustment $adjustment): void;

    public function generateCode(): string;
}
