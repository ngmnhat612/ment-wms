<?php

namespace App\Services\StockMovement;

use App\Models\Master\Product;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Lot;
use App\Models\Inventory\Serial;
use App\Models\StockMovement\StockReceipt;
use App\Models\StockMovement\StockReceiptLine;
use App\Models\StockMovement\StockReceiptDetail;
use App\Models\StockMovement\StockIssueDetail;
use App\Models\Stocktake\InventoryCheckDetail;
use App\Models\Stocktake\StockAdjustmentDetail;
use App\Enums\DocumentStatus;
use App\Enums\LotSerialStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Inventory\StockService;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;
use App\Repositories\Contracts\Inventory\LotRepositoryInterface;
use App\Repositories\Contracts\Inventory\SerialRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockReceiptRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;

class StockReceiptService
{
    public function __construct(
        private StockReceiptRepositoryInterface $receiptRepository,
        private StockService $stockService,
        private ProductRepositoryInterface $productRepository,
        private LotRepositoryInterface $lotRepository,
        private SerialRepositoryInterface $serialRepository,
        private CodeGeneratorService $codeGenerator,
    ) {}

    /**
     * Tạo phiếu nhập mới (luôn ở trạng thái Draft).
     * $lineRows: mảng lines[], mỗi line = 1 hàng UI (FLAT), gồm
     * product_id/uom_id/expected_qty + location_id/receiver_id/actual_qty/
     * lot_number/serial_numbers (chuỗi space-separated nếu là Lô+Sê-ri).
     * prepareLineRows() sẽ tự tách serial_numbers thành nhiều dòng detail con.
     */
    public function create(array $header, array $lineRows): StockReceipt
    {
        return DB::transaction(function () use ($header, $lineRows) {
            $receipt = $this->receiptRepository->create([
                'warehouse_id'        => $header['warehouse_id'],
                'stock_in_request_id' => $header['stock_in_request_id'] ?? null,
                'code'                => $header['code'] ?: $this->receiptRepository->generateCode(),
                'created_by'          => Auth::id(),
                'status'              => DocumentStatus::Draft->value,
                'note'                => $header['note'] ?? null,
                'receipt_date'        => $header['receipt_date'],
            ]);

            $this->receiptRepository->replaceDetails($receipt, $this->prepareLineRows($lineRows, $receipt));

            return $receipt;
        });
    }

    public function update(StockReceipt $receipt, array $header, array $lineRows): StockReceipt
    {
        $this->assertDraft($receipt, 'chỉnh sửa');

        return DB::transaction(function () use ($receipt, $header, $lineRows) {
            $oldLotIds = $receipt->details()->whereNotNull('lot_id')->pluck('lot_id')->unique()->all();

            $this->receiptRepository->update($receipt, [
                'stock_in_request_id' => $header['stock_in_request_id'] ?? null,
                'note'                => $header['note'] ?? null,
                'receipt_date'        => $header['receipt_date'],
            ]);

            $this->receiptRepository->replaceDetails($receipt, $this->prepareLineRows($lineRows, $receipt));

            $this->releaseUnusedLots($oldLotIds, $receipt);

            return $receipt->fresh();
        });
    }

    public function delete(StockReceipt $receipt): void
    {
        $this->assertDraft($receipt, 'xóa');

        DB::transaction(function () use ($receipt) {
            $lotIds = $receipt->details()->whereNotNull('lot_id')->pluck('lot_id')->unique()->all();

            $this->receiptRepository->delete($receipt);

            $this->releaseUnusedLots($lotIds);
        });
    }

    /**
     * Draft → Completed. Bước DUY NHẤT cập nhật tồn kho (qua StockService).
     * Giờ phải loop 2 tầng: lines -> details, vì product_id/uom_id nằm ở
     * line (cha), còn actual_qty/lot_id/serial_id/location_id nằm ở detail (con).
     */
public function approve(StockReceipt $receipt): void
    {
        if ($receipt->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể duyệt phiếu đang ở trạng thái Nháp.');
        }

        if ($receipt->lines()->count() === 0) {
            throw new \DomainException('Phiếu chưa có hàng hóa. Vui lòng thêm ít nhất một dòng.');
        }

        DB::transaction(function () use ($receipt) {
            $receipt->load('lines.details');

            foreach ($receipt->lines as $line) {
                foreach ($line->details as $detail) {
                    $qty = $detail->actual_qty ?? $line->expected_qty;
                    if ($qty <= 0) continue;

                    $this->stockService->increase([
                        'warehouse_id'     => $receipt->warehouse_id,
                        'product_id'       => $line->product_id,
                        'location_id'      => $detail->location_id,
                        'quantity'         => $qty,
                        'lot_id'           => $detail->lot_id,
                        'serial_id'        => $detail->serial_id,
                        'transaction_type' => StockService::TYPE_RECEIPT,
                        'reference_id'     => $receipt->id,
                        'reference_type'   => 'stock_receipt',
                        'reference_code'   => $receipt->code,
                        'note'             => "Nhập kho từ phiếu {$receipt->code}",
                        'created_by'       => Auth::id(),
                    ]);

                    $this->receiptRepository->updateDetailActualQty($detail, $qty); // + qua Repository
                }
            }

            $this->receiptRepository->update($receipt, [
                'status'      => DocumentStatus::Completed,
                'approved_by' => Auth::id(),
            ]);
        });
    }

    public function cancel(StockReceipt $receipt): void
    {
        if ($receipt->status === DocumentStatus::Completed) {
            throw new \DomainException('Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ($receipt->status === DocumentStatus::Cancelled) {
            throw new \DomainException('Phiếu đã được hủy trước đó.');
        }

        DB::transaction(function () use ($receipt) {
            $lotIds = $receipt->details()->whereNotNull('lot_id')->pluck('lot_id')->unique()->all();

            $this->receiptRepository->update($receipt, [
                'status' => DocumentStatus::Cancelled->value,
            ]);

            $this->releaseUnusedLots($lotIds);
        });
    }

    private function assertDraft(StockReceipt $receipt, string $action): void
    {
        if ($receipt->status !== DocumentStatus::Draft) {
            throw new \DomainException("Chỉ có thể {$action} phiếu ở trạng thái Nháp.");
        }
    }

    /**
     * Xóa các Lô không còn được dùng bởi bất kỳ phiếu/chi tiết nào khác
     * (stock_receipt_detail, stock_issue_detail), chưa gắn Serial nào,
     * và chưa phát sinh tồn kho thật (bảng stocks) — để số Lô (lot_number)
     * có thể được tái sử dụng cho phiếu tiếp theo thay vì luôn tăng dần.
     *
     * Chỉ gọi khi Hủy/Xóa phiếu Nháp (hoặc dọn lô cũ sau khi Update) —
     * phiếu Draft chưa từng chạy qua StockService::increase() nên chắc chắn
     * chưa có dòng nào trong `stocks` cho các lô này (an toàn để xóa).
     *
     * QUAN TRỌNG (bug đã sửa): khi gọi từ update() (đổi Lô cũ -> Lô khác),
     * $lotIds là các Lô của CHÍNH PHIẾU $receipt đang sửa, mà replaceDetails()
     * VỪA soft-delete. Việc check StockReceiptDetail::withTrashed() TRƯỚC ĐÂY
     * không loại trừ các dòng detail đã-trashed CỦA CHÍNH PHIẾU NÀY, nên luôn
     * thấy "vẫn còn dùng" (do chính bản ghi vừa xóa mềm) và KHÔNG BAO GIỜ xóa
     * được Lô cũ — Lô cũ tồn đọng vĩnh viễn trong DB dù không còn ai dùng,
     * khiến sau này không thể validate/gán lại đúng số Lô đó nữa (báo nhầm
     * "đã tồn tại"). $receipt (nullable, null khi gọi từ delete()/cancel())
     * cho phép loại trừ đúng các detail (kể cả đã trashed) CỦA PHIẾU NÀY khỏi
     * điều kiện "còn dùng" — chỉ tính là "còn dùng" nếu thuộc PHIẾU KHÁC.
     */
    private function releaseUnusedLots(array $lotIds, ?StockReceipt $receipt = null): void
    {
        if (empty($lotIds)) {
            return;
        }

        foreach ($lotIds as $lotId) {
            $usedByReceiptDetail = StockReceiptDetail::where('lot_id', $lotId)->exists();
            $usedByIssueDetail   = StockIssueDetail::where('lot_id', $lotId)->exists();
            $usedByCheckDetail   = DB::table('inventory_check_detail')->where('lot_id', $lotId)->exists();
            $usedByAdjustDetail  = DB::table('stock_adjustment_details')->where('lot_id', $lotId)->exists();
            $usedByStock         = Stock::where('lot_id', $lotId)->exists();
            $usedBySerial        = Serial::where('lot_id', $lotId)->exists();

            $stillUsed = $usedByReceiptDetail || $usedByIssueDetail || $usedByCheckDetail
                || $usedByAdjustDetail || $usedByStock || $usedBySerial;

            if (! $stillUsed) {
                StockReceiptDetail::onlyTrashed()->where('lot_id', $lotId)->forceDelete();
                StockIssueDetail::onlyTrashed()->where('lot_id', $lotId)->forceDelete();
                // inventory_check_detail / stock_adjustment_details KHÔNG có cột
                // deleted_at trong DB (migration thiếu softDeletes()) nên không có
                // gì để forceDelete — bỏ qua 2 dòng này.

                Lot::where('id', $lotId)->delete();
            }
        }
    }

    /**
     * Kiểm tra Lô $oldLotId (đang gán cho dòng vừa bị người dùng XÓA TRẮNG
     * ô Số Lô khi sửa phiếu Draft) còn AN TOÀN để TÁI SỬ DỤNG lại hay không,
     * dùng chung tiêu chí an toàn với releaseUnusedLots(): Lô đó chưa phát
     * sinh tồn kho thật, chưa gắn Serial nào, và không bị dòng nào KHÁC
     * (ngoài chính phiếu đang sửa — $receipt) tham chiếu tới.
     *
     * QUAN TRỌNG: hàm này chạy TRƯỚC replaceDetails() (bên trong
     * prepareLineRows()), nên các StockReceiptDetail cũ của CHÍNH PHIẾU
     * ĐANG SỬA vẫn còn "sống" (chưa soft-delete) — phải loại trừ chúng ra
     * khi kiểm tra "có dòng nào khác đang dùng Lô này", nếu không sẽ luôn
     * thấy Lô "đang bị chính mình dùng" và không bao giờ tái sử dụng được.
     *
     * @return Lot|null Lô nếu còn tồn tại và an toàn để tái sử dụng, null nếu
     *                   không (đã bị người khác dùng, hoặc đã bị xóa trước đó
     *                   — trường hợp hiếm, rơi về sinh số mới như bình thường).
     */
    private function reusableOldLot(int $oldLotId, int $productId, StockReceipt $receipt): ?Lot
    {
        $lot = Lot::where('id', $oldLotId)
            ->where('product_id', $productId)
            ->first();

        if (! $lot) {
            return null;
        }

        // Tại thời điểm hàm này chạy, replaceDetails() CHƯA chạy (đang trong
        // prepareLineRows()), nên detail cũ của CHÍNH phiếu này vẫn còn sống.
        // Phải loại trừ chúng ra, nếu không sẽ luôn thấy "đang bị chính mình dùng".
        $usedElsewhere = StockReceiptDetail::where('lot_id', $oldLotId)
                ->whereNotExists(function ($sub) use ($receipt) {
                    $sub->select(DB::raw(1))
                        ->from('stock_receipt_line')
                        ->whereColumn('stock_receipt_line.id', 'stock_receipt_detail.stock_receipt_line_id')
                        ->where('stock_receipt_line.stock_receipt_id', $receipt->id);
                })
                ->exists()
            || StockIssueDetail::where('lot_id', $oldLotId)->exists()
            || DB::table('inventory_check_detail')->where('lot_id', $oldLotId)->exists()
            || DB::table('stock_adjustment_details')->where('lot_id', $oldLotId)->exists()
            || Stock::where('lot_id', $oldLotId)->exists()
            || Serial::where('lot_id', $oldLotId)->exists();

        return $usedElsewhere ? null : $lot;
    }

    /**
     * Tầng CHA: chuẩn hóa từng dòng vật tư (line, = 1 hàng UI).
     *
     * Input mỗi $line (FLAT — không còn mảng details[] con từ người dùng nữa):
     *   product_id, uom_id, expected_qty, sn_id?, note?,
     *   location_id, receiver_id, actual_qty, lot_number?,
     *   serial_numbers?  (chuỗi text, mỗi mã Sê-ri cách nhau 1 <space>),
     *   sub_warehouse?, expiry_date?, supplier_id?, brand_id?
     *
     * Output: mảng cho StockReceiptRepository::replaceDetails() — mỗi line
     * kèm 'details' đã được TÁCH SẴN (1 dòng detail cho tracking=Lot,
     * hoặc N dòng detail — mỗi serial trong chuỗi 1 dòng — cho tracking=LotAndSerial).
     */
    private function prepareLineRows(array $lineRows, StockReceipt $receipt): array
    {
        $rows = [];

        foreach ($lineRows as $line) {
            if (empty($line['product_id']) || empty($line['expected_qty'])) {
                continue;
            }

            $rows[] = [
                'product_id'   => $line['product_id'],
                'uom_id'       => $line['uom_id'],
                'sn_id'        => $line['sn_id'] ?? null,
                'expected_qty' => $line['expected_qty'],
                'note'         => $line['note'] ?? null,
                'details'      => $this->prepareDetailRows($line, $receipt),
            ];
        }

        return $rows;
    }

    /**
     * Tầng CON: resolve lot_id/serial_id THẬT cho 1 line, TÁCH chuỗi
     * serial_numbers (space-separated) thành nhiều dòng detail — mỗi
     * serial 1 dòng, actual_qty=1/dòng — khi sản phẩm là LotAndSerial.
     * Với sản phẩm chỉ theo Lô, trả về đúng 1 dòng detail dùng actual_qty
     * người dùng nhập trực tiếp.
     *
     * Bảng lots có 2 cột tách biệt:
     *   lot_code   (NVARCHAR, unique)  - mã hiển thị, vd "LO12"
     *   lot_number (INT)               - số thứ tự, vd 12
     *
     * TrackingType chỉ có 2 giá trị:
     *   1 = Lot            -> Serial không dùng, actual_qty nhập tay
     *   2 = LotAndSerial   -> Serial bắt buộc, actual_qty PHẢI khớp số
     *       serial đếm được trong chuỗi (đã validate ở StockReceiptRequest,
     *       ở đây không validate lại — chỉ tách và resolve).
     *
     * Lô luôn được resolve/tạo mới bất kể tracking type. Nếu để trống
     * Mã Lô, hệ thống tự sinh (LO1, LO2, ...). Tất cả serial của cùng 1
     * line dùng chung 1 lot_id (đúng nghiệp vụ: 1 lô nhập về gồm nhiều serial).
     *
     * @param array        $line    dữ liệu flat của 1 line (đã qua StockReceiptRequest)
     * @param StockReceipt $receipt
     */
    private function prepareDetailRows(array $line, StockReceipt $receipt): array
    {
        $productId = $line['product_id'];
        // Trước: Product::find($productId);
        $product = $this->productRepository->findById($productId);

        $lotId          = $line['lot_id'] ?? null;
        $lotNumberInput = trim((string) ($line['lot_number'] ?? ''));
        $oldLotId       = ! empty($line['old_lot_id']) ? (int) $line['old_lot_id'] : null;
        $supplierId     = $line['supplier_id'] ?? null;

        // Lô luôn được resolve 1 LẦN cho cả line (dùng chung cho mọi serial con).
        if (! $lotId) {
            if ($lotNumberInput === '') {
                // Người dùng XÓA TRẮNG ô Lô. Nếu dòng này TRƯỚC ĐÓ đã có sẵn
                // 1 Lô (old_lot_id, gán lúc mở form Edit) và Lô đó vẫn CÒN AN
                // TOÀN để tái sử dụng (chưa phát sinh tồn kho/serial/gắn với
                // dòng nào khác — cùng điều kiện với releaseUnusedLots()), thì
                // GIỮ LẠI đúng số Lô cũ thay vì sinh số mới — tránh "nhảy cóc"
                // số Lô (vd Lô 5 -> xóa trắng -> Lô 6) khi thực chất không có
                // gì thay đổi và Lô 5 chưa từng được ai khác dùng.
                $reusableLot = $oldLotId ? $this->reusableOldLot($oldLotId, $productId, $receipt) : null;

                if ($reusableLot) {
                    $lotNumber = $reusableLot->lot_number;
                    $lotCode   = $reusableLot->lot_code;
                } else {
                    $generated = $this->generateUniqueLot($productId);
                    $lotNumber = $generated['number'];
                    $lotCode   = $generated['code'];
                }
            } else {
                $lotNumber = (int) $lotNumberInput;
                $lotCode   = 'LO' . $lotNumber;
            }

            $lot = $this->lotRepository->firstOrCreate(
                $productId,
                $lotCode,
                [
                    'lot_number'    => $lotNumber,
                    'supplier_id'   => $supplierId,
                    'received_date' => $receipt->receipt_date,
                    'expiry_date'   => $line['expiry_date'] ?? null,
                    'status'        => LotSerialStatus::InStock,
                ]
            );
            $lotId = $lot->id;
        }

        $commonAttrs = [
            'location_id'   => $line['location_id'] ?? null,
            'receiver_id'   => $line['receiver_id'] ?? null,
            'expiry_date'   => $line['expiry_date'] ?? null,
            'sub_warehouse' => $line['sub_warehouse'] ?? null,
            'note'          => $line['note'] ?? null,
        ];

        // Sản phẩm KHÔNG theo Sê-ri: 1 dòng detail duy nhất, actual_qty nhập tay.
        if (! $product?->isSerialTracked()) {
            return [array_merge($commonAttrs, [
                'lot_id'     => $lotId,
                'serial_id'  => null,
                'actual_qty' => is_numeric($line['actual_qty'] ?? null) ? $line['actual_qty'] : 0,
            ])];
        }

        // Sản phẩm theo Lô + Sê-ri: tách chuỗi "SN001 SN002 SN003" thành
        // N dòng detail, mỗi dòng 1 serial, actual_qty = 1/dòng.
        $serialNumbers = collect(preg_split('/\s+/', trim((string) ($line['serial_numbers'] ?? ''))))
            ->filter(fn ($s) => $s !== '')
            ->unique()
            ->values();

        $rows = [];
        foreach ($serialNumbers as $serialNumber) {
            // Trước: Serial::firstOrCreate(...)
            $serial = $this->serialRepository->firstOrCreate(
                $serialNumber,
                [
                    'lot_id' => $lotId,
                    'status' => LotSerialStatus::InStock,
                ]
            );

            $rows[] = array_merge($commonAttrs, [
                'lot_id'     => $lotId,
                'serial_id'  => $serial->id,
                'actual_qty' => 1,
            ]);
        }

        return $rows;
    }

    /**
     * Sinh số lô + mã lô tự động và đảm bảo không trùng ngay trong cùng 1 phiếu
     * (nhiều dòng cùng để trống Số Lô trong 1 lần submit).
     * lot_code có unique constraint ở DB nên đây là lớp bảo vệ ở tầng service,
     * tránh vi phạm ràng buộc khi generateLotCode() trả cùng 1 giá trị do
     * chưa kịp ghi xuống DB giữa các lần lặp.
     */
    private function generateUniqueLot(int $productId): array
    {
        do {
            $generated = $this->codeGenerator->generateLotCode($productId);
        } while (Lot::where('lot_number', $generated['number'])->where('product_id', $productId)->exists());

        return $generated;
    }
}