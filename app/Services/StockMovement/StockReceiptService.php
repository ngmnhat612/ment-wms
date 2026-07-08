<?php

namespace App\Services\StockMovement;

use App\Models\Inventory\Lot;
use App\Models\Master\Product;
use App\Models\StockMovement\StockReceipt;
use App\Models\Inventory\Serial;
use App\Enums\DocumentStatus;
use App\Enums\LotSerialStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\StockService;
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
                // - 'stock_request_id' => $header['stock_request_id'] ?? null,
                'stock_in_request_id' => $header['stock_in_request_id'] ?? null,
                'note'                => $header['note'] ?? null,
                'receipt_date'        => $header['receipt_date'],
            ]);

            $this->receiptRepository->replaceDetails($receipt, $this->prepareLineRows($lineRows, $receipt));

            $this->releaseUnusedLots($oldLotIds);

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
     */
    private function releaseUnusedLots(array $lotIds): void
    {
        if (empty($lotIds)) {
            return;
        }

        foreach ($lotIds as $lotId) {
            $stillUsed = \App\Models\StockMovement\StockReceiptDetail::where('lot_id', $lotId)->exists()
                || \App\Models\StockMovement\StockIssueDetail::where('lot_id', $lotId)->exists()
                || \App\Models\Inventory\Stock::where('lot_id', $lotId)->exists()
                || Serial::where('lot_id', $lotId)->exists();

            if (! $stillUsed) {
                Lot::where('id', $lotId)->delete();
            }
        }
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
        $supplierId     = $line['supplier_id'] ?? null;

        // Lô luôn được resolve 1 LẦN cho cả line (dùng chung cho mọi serial con).
        if (! $lotId) {
            if ($lotNumberInput === '') {
                $generated = $this->generateUniqueLot();
                $lotNumber = $generated['number'];
                $lotCode   = $generated['code'];
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
                'actual_qty' => $line['actual_qty'] ?? null,
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
    private function generateUniqueLot(): array
    {
        do {
            $generated = $this->codeGenerator->generateLotCode();
        } while (Lot::where('lot_number', $generated['number'])->exists());

        return $generated;
    }
}