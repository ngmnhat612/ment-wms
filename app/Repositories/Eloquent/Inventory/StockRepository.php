<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Enums\LotSerialStatus;
use App\Enums\LocationType;
use App\Models\Inventory\Serial;
use App\Models\Inventory\Stock;
use App\Models\Master\Location;
use App\Models\StockMovement\StockIssueDetail;
use App\Repositories\Contracts\Inventory\StockRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class StockRepository implements StockRepositoryInterface
{
    /**
     * Danh sách Stock thô của 1 sản phẩm tại các vị trí thực (Internal),
     * KHÔNG lọc theo available/reserved, KHÔNG tính toán gì thêm — Repository
     * chỉ truy cập dữ liệu, mọi nghiệp vụ (lọc adjusted qty, offset own-reserved)
     * do Service Layer quyết định (Rule #3).
     */
    public function availableForIssue(int $productId): Collection
    {
        return Stock::with(['currentLocation', 'lot', 'serial'])
            ->where('product_id', $productId)
            ->whereHas('currentLocation', fn ($q) => $q->where('type', LocationType::Internal->value))
            ->get();
    }

    public function ownReservedByLotLocation(int $issueId, int $productId): array
    {
        $details = StockIssueDetail::query()
            ->whereHas('line', fn ($q) => $q->where('stock_issue_id', $issueId)->where('product_id', $productId))
            ->with('line')
            ->get();

        $result = [];
        foreach ($details as $detail) {
            if (! $detail->location_id || ! $detail->lot_id) continue;

            $key = $detail->location_id . ':' . $detail->lot_id;
            $qty = $detail->serial_id ? 1 : (float) ($detail->line->expected_qty ?? $detail->actual_qty ?? 0);
            $result[$key] = ($result[$key] ?? 0) + $qty;
        }

        return $result;
    }

    public function serialsInStock(int $productId, int $lotId): Collection
    {
        return Serial::where('product_id', $productId)
            ->where('lot_id', $lotId)
            ->where('status', LotSerialStatus::InStock->value)
            ->get();
    }

    // ===== INVENTORY LIST (moved from InventoryController) =====

    /**
     * Query nền dùng chung cho danh sách + KPI + tổng — chỉ join product/location
     * và áp filter show_virtual, chưa group by.
     */
    private function baseQuery(bool $showVirtual): Builder
    {
        return Stock::query()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('locations', 'locations.id', '=', 'stocks.current_location_id')
            ->when(! $showVirtual, fn ($q) => $q->where('locations.type', LocationType::Internal->value));
    }

    public function kpiSummary(bool $showVirtual): array
    {
        $base = $this->baseQuery($showVirtual);

        $totalSkuCount = (clone $base)
            ->where('stocks.quantity', '>', 0)
            ->distinct()
            ->count('stocks.product_id');

        $totalQty        = (float) (clone $base)->sum('stocks.quantity');
        $reservedQty     = (float) (clone $base)->sum('stocks.reserved_qty');
        $quarantineCount = (clone $base)
            ->where('stocks.status', LotSerialStatus::Expired->value)
            ->count();

        return [
            'totalSkuCount'   => $totalSkuCount,
            'totalQty'        => $totalQty,
            'reservedQty'     => $reservedQty,
            'quarantineCount' => $quarantineCount,
        ];
    }

    public function filteredTotals(array $filters): array
    {
        $showVirtual = $filters['show_virtual'] ?? false;
        $showZero    = $filters['show_zero'] ?? false;
        $search      = $filters['search'] ?? null;
        $categoryId  = $filters['category_id'] ?? null;
        $productId   = $filters['product_id'] ?? null;
        $locationId  = $filters['location_id'] ?? null;
        $status      = $filters['status'] ?? null;

        // Phải gộp nhóm (product+location+lot) TRƯỚC khi lọc theo status/quantity,
        // giống hệt searchStockList() — nếu không, tổng ở đây sẽ không khớp với
        // danh sách hiển thị phía trên (vd: 1 sê-ri hết hàng trong 1 lô còn tồn
        // sẽ bị/không bị tính sai tùy điều kiện áp ở tầng dòng-lẻ).
        $statusExpr = 'CASE
            WHEN MAX(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) = 1 THEN ?
            WHEN SUM(stocks.quantity) <= 0 THEN ?
            ELSE ?
        END';
        $statusExprBindings = [
            LotSerialStatus::Expired->value,
            LotSerialStatus::Expired->value,
            LotSerialStatus::Consumed->value,
            LotSerialStatus::InStock->value,
        ];

        $grouped = $this->baseQuery($showVirtual)
            ->leftJoin('lots', 'lots.id', '=', 'stocks.lot_id')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('products.code', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%")
                        ->orWhere('lots.lot_number', 'like', "%{$search}%")
                        ->orWhereExists(function ($sub) use ($search) {
                            $sub->select('id')->from('serials')
                                ->whereColumn('serials.id', 'stocks.serial_id')
                                ->where('serials.serial_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->when($productId, fn ($q) => $q->where('stocks.product_id', $productId))
            ->when($locationId, fn ($q) => $q->where('stocks.current_location_id', $locationId))
            ->select([
                'stocks.product_id',
                'stocks.current_location_id',
                'stocks.lot_id',
            ])
            ->selectRaw('SUM(stocks.quantity) as g_quantity')
            ->selectRaw('SUM(stocks.reserved_qty) as g_reserved_qty')
            ->selectRaw('SUM(stocks.available_qty) as g_available_qty')
            ->groupBy('stocks.product_id', 'stocks.current_location_id', 'stocks.lot_id');

        if ($status) {
            $grouped->havingRaw("{$statusExpr} = ?", [...$statusExprBindings, $status]);
        } else {
            $grouped->havingRaw(
                'MAX(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) = 0',
                [LotSerialStatus::Expired->value]
            );

            if (! $showZero) {
                $grouped->havingRaw('SUM(stocks.quantity) > 0');
            }
        }

        $result = DB::query()
            ->fromSub($grouped, 'g')
            ->selectRaw('SUM(g_quantity) as sum_quantity, SUM(g_reserved_qty) as sum_reserved_qty, SUM(g_available_qty) as sum_available_qty')
            ->first();

        return [
            'quantity'      => (float) ($result->sum_quantity ?? 0),
            'reserved_qty'  => (float) ($result->sum_reserved_qty ?? 0),
            'available_qty' => (float) ($result->sum_available_qty ?? 0),
        ];
    }

    public function searchStockList(array $filters): LengthAwarePaginator
    {
        $showVirtual = $filters['show_virtual'] ?? false;
        $showZero    = $filters['show_zero'] ?? false;
        $search      = $filters['search'] ?? null;
        $categoryId  = $filters['category_id'] ?? null;
        $productId   = $filters['product_id'] ?? null;
        $locationId  = $filters['location_id'] ?? null;
        $status      = $filters['status'] ?? null;
        $sort        = $filters['sort'] ?? null;
        $dir         = ($filters['dir'] ?? null) === 'desc' ? 'desc' : 'asc';
        $perPage     = $filters['per_page'] ?? 20;

        $query = $this->baseQuery($showVirtual)
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'products.uom_id')
            ->leftJoin('lots', 'lots.id', '=', 'stocks.lot_id')
            ->leftJoin('locations as prev_locations', 'prev_locations.id', '=', 'stocks.previous_location_id')
            ->leftJoin('reorder_rules', function ($join) {
                $join->on('reorder_rules.product_id', '=', 'stocks.product_id')
                    ->on('reorder_rules.warehouse_id', '=', 'stocks.warehouse_id');
            });

        // LƯU Ý: KHÔNG lọc theo stocks.quantity hay stocks.status ở tầng WHERE.
        // Một nhóm (product+location+lot) có thể gồm NHIỀU dòng stocks con
        // (nhiều sê-ri). Trạng thái/tồn kho hiển thị là trạng thái TỔNG HỢP
        // của cả nhóm, không phải của từng dòng con — nên mọi điều kiện lọc
        // theo status/quantity phải chạy ở HAVING, sau khi GROUP BY đã gộp
        // toàn bộ dòng con lại. Lọc ở WHERE sẽ cắt bớt dòng con TRƯỚC KHI gộp,
        // làm sai lệch SUM/status tổng hợp của nhóm.

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.code', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('lots.lot_number', 'like', "%{$search}%")
                    ->orWhereExists(function ($sub) use ($search) {
                        $sub->select('id')->from('serials')
                            ->whereColumn('serials.id', 'stocks.serial_id')
                            ->where('serials.serial_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        if ($productId) {
            $query->where('stocks.product_id', $productId);
        }

        if ($locationId) {
            $query->where('stocks.current_location_id', $locationId);
        }

        // Biểu thức tính status TỔNG HỢP của nhóm, dùng chung cho SELECT và HAVING:
        // - Còn ít nhất 1 dòng con Expired  → nhóm là Expired (ưu tiên cao nhất)
        // - Ngược lại, tổng SUM(quantity) <= 0 → nhóm là Consumed (Hết hàng)
        // - Còn lại → InStock
        $statusExpr = 'CASE
            WHEN MAX(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) = 1 THEN ?
            WHEN SUM(stocks.quantity) <= 0 THEN ?
            ELSE ?
        END';
        $statusExprBindings = [
            LotSerialStatus::Expired->value,
            LotSerialStatus::Expired->value,
            LotSerialStatus::Consumed->value,
            LotSerialStatus::InStock->value,
        ];

        $query->select([
                'products.id as product_id',
                'stocks.current_location_id',
                'stocks.lot_id',
                'products.code as product_code',
                'products.name as product_name',
                'categories.code as category_code',
                'locations.code as location_code',
                'locations.name as location_name',
                'uoms.name as uom_name',
                'lots.lot_number',
                'lots.expiry_date',
                'reorder_rules.min_qty as min_stock',
            ])
            ->selectRaw('SUM(stocks.quantity) as quantity')
            ->selectRaw('SUM(stocks.reserved_qty) as reserved_qty')
            ->selectRaw('SUM(stocks.available_qty) as available_qty')
            ->selectRaw('COUNT(stocks.serial_id) as serial_count')
            ->selectRaw("{$statusExpr} as status", $statusExprBindings)
            ->selectRaw('MAX(stocks.created_at) as created_at')
            // previous_location lấy giá trị đại diện của nhóm (không dùng để gộp
            // nhóm) — MAX() vô hại vì đây chỉ là dữ liệu hiển thị "vị trí trước",
            // không phải khóa nghiệp vụ. Ưu tiên bản ghi mới nhất bằng cách join
            // theo stocks.id lớn nhất nếu cần độ chính xác cao hơn, còn ở mức
            // hiển thị hiện tại MAX(previous_location_id) là đủ.
            ->selectRaw('MAX(stocks.previous_location_id) as previous_location_id')
            ->selectRaw('MAX(prev_locations.code) as prev_location_code')
            ->selectRaw('MAX(prev_locations.name) as prev_location_name')
            ->groupBy([
                'products.id', 'stocks.current_location_id', 'stocks.lot_id',
                'products.code', 'products.name',
                'categories.code',
                'locations.code', 'locations.name',
                'uoms.name', 'lots.lot_number', 'lots.expiry_date',
                'reorder_rules.min_qty',
            ]);

        if ($status) {
            // Người dùng chủ động chọn status: lọc đúng theo status tổng hợp,
            // KHÔNG áp thêm điều kiện quantity>0 (vd: chọn "Hết hàng" phải hiện
            // được nhóm có tổng qty=0, kể cả khi showZero=false).
            $query->havingRaw("{$statusExpr} = ?", [...$statusExprBindings, $status]);
        } else {
            // Mặc định KHÔNG hiển thị nhóm Hết hạn — chỉ hiện khi người dùng
            // chủ động chọn filter Trạng thái = Hết hạn.
            $query->havingRaw(
                'MAX(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) = 0',
                [LotSerialStatus::Expired->value]
            );

            if (! $showZero) {
                // Mặc định cũng ẩn nhóm đã hết hàng hoàn toàn (tổng qty = 0),
                // trừ khi người dùng tick "hiện số lượng 0".
                $query->havingRaw('SUM(stocks.quantity) > 0');
            }
        }

        $sortColumns = [
            'product_code'  => 'products.code',
            'product_name'  => 'products.name',
            'expiry_date'   => 'lots.expiry_date',
            'quantity'      => DB::raw('SUM(stocks.quantity)'),
            'available_qty' => DB::raw('SUM(stocks.available_qty)'),
        ];

        if ($sort && array_key_exists($sort, $sortColumns)) {
            $query->orderBy($sortColumns[$sort], $dir);
        } else {
            // Mặc định: mới tạo trước (giảm dần theo created_at)
            $query->orderByRaw('MAX(stocks.created_at) desc');
        }

        // Tie-breaker ổn định để tránh xáo trộn thứ tự giữa các trang
        $query->when($sort !== 'product_code', fn ($q) => $q->orderBy('products.code'))
            ->orderBy('locations.code');

        return $query->paginate($perPage)->withQueryString();
    }

    public function lotSerialsAt(int $productId, int $locationId, ?int $lotId): Collection
    {
        return Stock::query()
            ->join('serials', 'serials.id', '=', 'stocks.serial_id')
            ->where('stocks.product_id', $productId)
            ->where('stocks.current_location_id', $locationId)
            ->when(
                $lotId !== null,
                fn ($q) => $q->where('stocks.lot_id', $lotId),
                fn ($q) => $q->whereNull('stocks.lot_id')
            )
            ->select([
                'serials.serial_number',
                'stocks.status',
                'stocks.quantity',
            ])
            ->orderBy('serials.serial_number')
            ->get();
    }
}