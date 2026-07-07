<?php

namespace App\Services;

use App\Enums\LotSerialStatus;
use App\Models\Inventory\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service DUY NHẤT được phép ghi/đọc trực tiếp lên bảng `stocks`.
 * Mọi thao tác tăng/giảm/giữ chỗ tồn kho từ các module khác (StockReceiptService,
 * StockIssueService, StockAdjustmentService...) đều phải đi qua đây (Rule #4).
 *
 * Quy tắc dữ liệu (theo schema `stocks`):
 * - lot_id: luôn có giá trị (NOT NULL) — không tồn tại hàng "không theo dõi lô".
 * - serial_id: có thể NULL.
 *      + Hàng theo lô:            lot_id có, serial_id NULL
 *      + Hàng theo lô + sê-ri:    lot_id có, serial_id có
 * - available_qty là cột computed (quantity - reserved_qty), không được ghi trực tiếp.
 */
class StockService
{
    public const TYPE_RECEIPT    = 'receipt';
    public const TYPE_ISSUE      = 'issue';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_TRANSFER   = 'transfer';

    /**
     * Gợi ý các dòng tồn kho khả dụng để xuất cho 1 sản phẩm, theo FEFO
     * (ưu tiên hạn dùng gần nhất) — fallback FIFO khi không có expiry_date.
     * Dùng ở bước Approve của phiếu xuất, TRƯỚC khi trừ tồn thật.
     *
     * @return array<int, array{location_id:int, qty_suggest:float, lot_id:int, serial_id:?int}>
     */
    public function suggestStockForIssue(int $productId, float $quantity, ?int $locationId = null): array
    {
        $query = Stock::query()
            ->where('product_id', $productId)
            ->where('status', LotSerialStatus::InStock->value)
            ->whereRaw('(quantity - reserved_qty) > 0')
            ->when($locationId, fn ($q) => $q->where('current_location_id', $locationId))
            ->join('lots', 'stocks.lot_id', '=', 'lots.id')
            ->orderByRaw('CASE WHEN lots.expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('lots.expiry_date')
            ->orderBy('lots.received_date')
            ->orderBy('stocks.id')
            ->select('stocks.*');

        $remaining     = $quantity;
        $suggestions   = [];

        foreach ($query->get() as $stock) {
            if ($remaining <= 0.0001) {
                break;
            }

            $available = (float) $stock->quantity - (float) $stock->reserved_qty;
            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);

            $suggestions[] = [
                'location_id'  => $stock->current_location_id,
                'qty_suggest'  => $take,
                'lot_id'       => $stock->lot_id,
                'serial_id'    => $stock->serial_id,
            ];

            $remaining -= $take;
        }

        return $suggestions;
    }

    /**
     * Giữ chỗ (reserved_qty += quantity) trên dòng stock khớp
     * product_id + current_location_id + lot_id + serial_id.
     *
     * @throws \DomainException nếu không đủ tồn khả dụng để giữ chỗ.
     */
    public function reserve(array $params): void
    {
        DB::transaction(function () use ($params) {
            $stock = $this->lockStockRow($params);

            $available = (float) $stock->quantity - (float) $stock->reserved_qty;

            if ($available < (float) $params['quantity']) {
                throw new \DomainException(
                    "Không đủ tồn khả dụng để giữ chỗ cho sản phẩm ID {$params['product_id']}."
                );
            }

            $stock->increment('reserved_qty', $params['quantity']);
        });
    }

    /**
     * Bỏ giữ chỗ (reserved_qty -= quantity) — dùng khi Complete (trước khi decrease)
     * hoặc khi Cancel một phiếu đã Approved.
     */
    public function release(array $params): void
    {
        DB::transaction(function () use ($params) {
            $stock = $this->lockStockRow($params);

            $newReserved = max(0, (float) $stock->reserved_qty - (float) $params['quantity']);
            $stock->update(['reserved_qty' => $newReserved]);
        });
    }

    /**
     * Trừ tồn thật (quantity -= quantity) — bước DUY NHẤT trừ tồn kho khi
     * phiếu xuất chuyển Approved → Completed.
     *
     * @throws \DomainException nếu không đủ tồn thật.
     */
    public function decrease(array $params): void
    {
        DB::transaction(function () use ($params) {
            $stock = $this->lockStockRow($params);

            if ((float) $stock->quantity < (float) $params['quantity']) {
                throw new \DomainException(
                    "Không đủ tồn kho thật để trừ cho sản phẩm ID {$params['product_id']}."
                );
            }

            $stock->update([
                'quantity'   => (float) $stock->quantity - (float) $params['quantity'],
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Cộng tồn thật (quantity += quantity) — bước DUY NHẤT cộng tồn kho khi
     * phiếu nhập chuyển Approved → Completed. Tự tạo dòng `stocks` mới nếu
     * chưa tồn tại tổ hợp product_id+lot_id+serial_id+current_location_id.
     */
    public function increase(array $params): void
    {
        DB::transaction(function () use ($params) {
            $stock = Stock::query()
                ->where('warehouse_id', $params['warehouse_id'])
                ->where('product_id', $params['product_id'])
                ->where('current_location_id', $params['location_id'])
                ->where('lot_id', $params['lot_id'])
                ->when(
                    $params['serial_id'] ?? null,
                    fn ($q) => $q->where('serial_id', $params['serial_id']),
                    fn ($q) => $q->whereNull('serial_id'),
                )
                ->lockForUpdate()
                ->first();

            if ($stock) {
                $stock->update([
                    'quantity'   => (float) $stock->quantity + (float) $params['quantity'],
                    'status'     => LotSerialStatus::InStock->value,
                    'updated_at' => now(),
                ]);

                return;
            }

            Stock::create([
                'warehouse_id'          => $params['warehouse_id'],
                'product_id'            => $params['product_id'],
                'previous_location_id'  => null,
                'current_location_id'   => $params['location_id'],
                'lot_id'                => $params['lot_id'],
                'serial_id'             => $params['serial_id'] ?? null,
                'quantity'              => $params['quantity'],
                'reserved_qty'          => 0,
                'updated_at'            => now(),
                'status'                => LotSerialStatus::InStock->value,
            ]);
        });
    }

    /**
     * Khóa (lockForUpdate) đúng 1 dòng stock khớp tổ hợp product+location+lot+serial.
     * Dùng chung cho reserve()/release()/decrease() — các thao tác luôn tác động
     * lên 1 dòng đã tồn tại sẵn (không tự tạo mới, khác với increase()).
     *
     * @throws \DomainException nếu không tìm thấy dòng stock tương ứng.
     */
    private function lockStockRow(array $params): Stock
    {
        $stock = Stock::query()
            ->where('product_id', $params['product_id'])
            ->where('current_location_id', $params['location_id'])
            ->where('lot_id', $params['lot_id'])
            ->when(
                $params['serial_id'] ?? null,
                fn ($q) => $q->where('serial_id', $params['serial_id']),
                fn ($q) => $q->whereNull('serial_id'),
            )
            ->lockForUpdate()
            ->first();

        if (! $stock) {
            throw new \DomainException(
                "Không tìm thấy dòng tồn kho tương ứng cho sản phẩm ID {$params['product_id']}."
            );
        }

        return $stock;
    }

    /**
     * Tổng khả dụng (quantity - reserved_qty) của 1 sản phẩm trong 1 kho.
     */
    public function availableQty(int $productId, int $warehouseId): float
    {
        return (float) Stock::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('COALESCE(SUM(quantity - reserved_qty), 0) as total')
            ->value('total');
    }
}