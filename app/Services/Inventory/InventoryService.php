<?php

namespace App\Services\Inventory;

use App\Events\StockLocationUpdated;
use App\Repositories\Contracts\Inventory\StockRepositoryInterface;
use App\Repositories\Contracts\Master\CategoryRepositoryInterface;
use App\Repositories\Contracts\Master\LocationRepositoryInterface;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;
use App\Services\Inventory\StockService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly StockService $stockService,
    ) {}

    // ===== READ =====

    /**
     * Danh sách tồn kho hiện tại (gộp theo product + location + lot) + KPI + tổng,
     * dùng cho màn Tồn kho (InventoryController::index).
     */
    public function getStockList(array $filters): array
    {
        $showVirtual = (bool) ($filters['show_virtual'] ?? false);

        $kpi     = $this->stockRepository->kpiSummary($showVirtual);
        $totals  = $this->stockRepository->filteredTotals($filters);
        $stocks  = $this->stockRepository->searchStockList($filters);

        return [
            'stocks'                    => $stocks,
            'totalSkuCount'             => $kpi['totalSkuCount'],
            'totalQty'                  => $kpi['totalQty'],
            'reservedQty'               => $kpi['reservedQty'],
            'quarantineCount'           => $kpi['quarantineCount'],
            'filteredTotalQuantity'     => $totals['quantity'],
            'filteredTotalReservedQty'  => $totals['reserved_qty'],
            'filteredTotalAvailableQty' => $totals['available_qty'],
        ];
    }

    /**
     * Dữ liệu filter dropdown (danh mục, sản phẩm, vị trí) cho màn Tồn kho.
     * Lấy TOÀN BỘ (không lọc active) vì tồn kho cũ có thể thuộc danh mục/
     * sản phẩm/vị trí đã ngừng sử dụng — người dùng vẫn cần lọc/xem được.
     */
    public function getFilterOptions(): array
    {
        return [
            'categories' => $this->categoryRepository->allOrdered(),
            'products'   => $this->productRepository->allOrdered(),
            'locations'  => $this->locationRepository->allOrdered(),
        ];
    }

    /**
     * Danh sách serial thuộc 1 lô tại 1 vị trí cụ thể (modal "Xem chi tiết sê-ri").
     */
    public function getLotSerials(int $productId, int $locationId, ?int $lotId): Collection
    {
        return $this->stockRepository->lotSerialsAt($productId, $locationId, $lotId);
    }

    // ===== WRITE =====

    /**
     * Cập nhật nhanh vị trí kho cho 1 lô/sản phẩm tại vị trí hiện tại,
     * không cần tạo phiếu chuyển kho.
     *
     * Rule #4: InventoryService KHÔNG được ghi trực tiếp lên bảng `stocks`.
     * Việc ghi thực tế được ủy quyền cho StockService::relocate() —
     * service duy nhất được phép ghi/đọc trực tiếp bảng này.
     */
    public function updateLocation(array $data): void
    {
        $productId      = (int) $data['product_id'];
        $lotId          = (int) $data['lot_id']; // lot_id luôn NOT NULL theo schema `stocks`
        $fromLocationId = (int) $data['from_location_id'];
        $toLocationId   = (int) $data['to_location_id'];

        try {
            $affected = $this->stockService->relocate([
                'product_id'       => $productId,
                'lot_id'           => $lotId,
                'from_location_id' => $fromLocationId,
                'to_location_id'   => $toLocationId,
            ]);
        } catch (\DomainException $e) {
            throw ValidationException::withMessages([
                'to_location_id' => $e->getMessage(),
            ]);
        }

        event(new StockLocationUpdated($productId, $lotId, $fromLocationId, $toLocationId, $affected));
    }
}