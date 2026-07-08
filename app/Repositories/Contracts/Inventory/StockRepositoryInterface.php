<?php

namespace App\Repositories\Contracts\Inventory;

use Illuminate\Support\Collection;

interface StockRepositoryInterface
{
    /**
     * Tồn khả dụng theo sản phẩm, kèm vị trí/lô/serial — dùng cho AJAX
     * gợi ý vị trí khi tạo Phiếu xuất (StockIssueService::getAvailableStockForIssue()).
     */
    public function availableForIssue(int $productId): Collection;

    /**
     * Danh sách serial đang tồn kho (status = InStock) của 1 Lô cụ thể —
     * dùng để "bung" mỗi dòng stock theo Lô thành nhiều dòng theo Sê-ri
     * khi sản phẩm quản lý theo Lô + Sê-ri.
     */
    public function serialsInStock(int $productId, int $lotId): Collection;
}