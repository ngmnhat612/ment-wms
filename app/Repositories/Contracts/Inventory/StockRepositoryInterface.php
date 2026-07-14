<?php

namespace App\Repositories\Contracts\Inventory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockRepositoryInterface
{
    /**
     * Tồn khả dụng theo sản phẩm, kèm vị trí/lô/serial — dùng cho AJAX
     * gợi ý vị trí khi tạo Phiếu xuất (StockIssueService::getAvailableStockForIssue()).
     */
        public function availableForIssue(int $productId, ?int $excludeIssueId = null): Collection;

    /**
     * Danh sách serial đang tồn kho (status = InStock) của 1 Lô cụ thể —
     * dùng để "bung" mỗi dòng stock theo Lô thành nhiều dòng theo Sê-ri
     * khi sản phẩm quản lý theo Lô + Sê-ri.
     */
    public function serialsInStock(int $productId, int $lotId): Collection;

    /**
     * Danh sách tồn kho hiện tại (gộp theo product + location + lot),
     * có filter + phân trang — dùng cho màn Tồn kho (inventory.index).
     */
    public function searchStockList(array $filters): LengthAwarePaginator;

    /**
     * Tổng KPI (SKU count, tổng SL, đang giữ, số dòng cảnh báo hết hạn)
     * theo cùng bộ filter show_virtual — dùng cho các thẻ KPI đầu trang.
     */
    public function kpiSummary(bool $showVirtual): array;

    /**
     * Tổng số lượng/đang giữ/khả dụng của TOÀN BỘ dòng stock khớp filter hiện tại
     * (không group by), dùng để hiển thị dòng tổng cuối bảng.
     */
    public function filteredTotals(array $filters): array;

    /**
     * Danh sách serial thuộc 1 lô tại 1 vị trí cụ thể (modal "Xem chi tiết sê-ri").
     */
    public function lotSerialsAt(int $productId, int $locationId, ?int $lotId): Collection;
}