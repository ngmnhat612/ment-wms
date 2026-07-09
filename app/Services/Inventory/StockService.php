<?php

namespace App\Services\Inventory;

use App\Enums\LotSerialStatus;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Lot;
use App\Models\Inventory\Serial;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Master\Product;

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
            ->join('lots', 'stocks.lot_id', '=', 'lots.id')
            ->where('stocks.product_id', $productId)
            ->where('stocks.status', LotSerialStatus::InStock->value)
            ->whereRaw('(stocks.quantity - stocks.reserved_qty) > 0')
            ->when($locationId, fn ($q) => $q->where('stocks.current_location_id', $locationId))
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
                    "Không đủ tồn khả dụng để giữ chỗ cho vật tư {$this->productLabel($params['product_id'])}."
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
                    "Không đủ tồn kho thật để trừ cho vật tư {$this->productLabel($params['product_id'])}."
                );
            }

            $newQuantity = (float) $stock->quantity - (float) $params['quantity'];

            $stock->update([
                'quantity'   => $newQuantity,
                'status'     => $newQuantity <= 0.0001
                    ? LotSerialStatus::Consumed->value
                    : LotSerialStatus::InStock->value,
                'updated_at' => now(),
            ]);

            // Sê-ri hết hàng ngay khi bị xuất hết (mỗi serial_id ứng với 1 dòng stock, qty=1)
            if (! empty($params['serial_id']) && $newQuantity <= 0.0001) {
                Serial::whereKey($params['serial_id'])
                    ->update(['status' => LotSerialStatus::Consumed->value]);
            }

            $this->syncLotStatus($params['lot_id']);
        });
    }

    /**
     * Đồng bộ status của Lot dựa trên tổng tồn thật (quantity) của TẤT CẢ dòng
     * stock thuộc lot_id đó, trên toàn hệ thống (mọi kho/vị trí).
     * Tổng = 0 → Lot chuyển Consumed (Hết hàng).
     */
    private function syncLotStatus(int $lotId): void
    {
        $totalQty = (float) Stock::query()
            ->where('lot_id', $lotId)
            ->sum('quantity');

        if ($totalQty <= 0.0001) {
            Lot::whereKey($lotId)->update(['status' => LotSerialStatus::Consumed->value]);
        }
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
     * Chuyển vị trí kho cho TẤT CẢ dòng stock khớp product_id + lot_id + vị trí cũ,
     * KHÔNG qua phiếu chuyển kho (dùng cho "Chỉnh sửa vị trí nhanh" ở màn Tồn kho).
     * Set previous_location_id = vị trí cũ, current_location_id = vị trí mới.
     * Không thay đổi quantity/reserved_qty — chỉ đổi vị trí vật lý.
     *
     * Lưu ý: 1 dòng hiển thị trên bảng Tồn kho (group by product+lot+location)
     * có thể ứng với NHIỀU dòng `stocks` thật nếu sản phẩm theo dõi sê-ri
     * (mỗi serial_id là 1 dòng riêng). Vì vậy phải chuyển toàn bộ, không chỉ 1 dòng.
     *
     * @return int Số dòng đã được chuyển vị trí.
     * @throws \DomainException nếu không có dòng stock nào khớp.
     */
    public function relocate(array $params): int
    {
        return DB::transaction(function () use ($params) {
            $stocks = Stock::query()
                ->where('product_id', $params['product_id'])
                ->where('current_location_id', $params['from_location_id'])
                ->where('lot_id', $params['lot_id'])
                ->lockForUpdate()
                ->get();
 
            if ($stocks->isEmpty()) {
                throw new \DomainException(
                    "Không tìm thấy tồn kho tương ứng cho vật tư {$this->productLabel($params['product_id'])} tại vị trí hiện tại."
                );
            }
 
            foreach ($stocks as $stock) {
                $stock->update([
                    'previous_location_id' => $params['from_location_id'],
                    'current_location_id'  => $params['to_location_id'],
                    'updated_at'            => now(),
                ]);
            }
 
            return $stocks->count();
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
                "Không tìm thấy dòng tồn kho tương ứng cho vật tư {$this->productLabel($params['product_id'])}."
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

    /**
     * Nhãn hiển thị "Mã vật tư - Tên vật tư" cho thông báo lỗi, thay vì
     * lộ product_id kỹ thuật ra ngoài UI. Fallback về ID nếu không tìm
     * thấy sản phẩm (trường hợp hiếm, tránh crash khi báo lỗi).
     */
    private function productLabel(int $productId): string
    {
        $product = Product::find($productId);

        return $product
            ? "{$product->code} - {$product->name}"
            : "ID {$productId}";
    }
}