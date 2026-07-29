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

    /**
     * Cập nhật phiếu đang ở trạng thái Draft.
     *
     * Trước khi ghi đè details (replaceDetails), phải chốt lại danh sách
     * lot_id/serial_id CŨ của phiếu (trước khi sửa) — để sau khi details
     * mới đã được ghi, biết chính xác Lô/Sê-ri nào KHÔNG còn xuất hiện
     * trong danh sách mới (bị người dùng xóa/đổi mã) và cần được dọn dẹp
     * nếu không còn ai dùng nữa.
     *
     * Thứ tự dọn dẹp BẮT BUỘC: Sê-ri trước, Lô sau — vì điều kiện an toàn
     * để xóa 1 Lô có kiểm tra "còn Serial nào gắn với Lô đó không"; nếu dọn
     * Lô trước, các Serial cũ (chưa kịp xóa) sẽ khiến Lô luôn bị coi là
     * "còn dùng" và không bao giờ được giải phóng.
     */
    public function update(StockReceipt $receipt, array $header, array $lineRows): StockReceipt
    {
        $this->assertDraft($receipt, 'chỉnh sửa');

        return DB::transaction(function () use ($receipt, $header, $lineRows) {
            $oldLotIds    = $receipt->details()->whereNotNull('lot_id')->pluck('lot_id')->unique()->all();
            $oldSerialIds = $receipt->details()->whereNotNull('serial_id')->pluck('serial_id')->unique()->all();

            $this->receiptRepository->update($receipt, [
                'stock_in_request_id' => $header['stock_in_request_id'] ?? null,
                'note'                => $header['note'] ?? null,
                'receipt_date'        => $header['receipt_date'],
            ]);

            $this->receiptRepository->replaceDetails($receipt, $this->prepareLineRows($lineRows, $receipt));

            // Dọn Sê-ri TRƯỚC, Lô SAU (xem lý do ở docblock phía trên).
            $this->releaseUnusedSerials($oldSerialIds);
            $this->releaseUnusedLots($oldLotIds);

            return $receipt->fresh();
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

    /**
     * Hủy phiếu Draft (chưa từng Approve, chưa từng phát sinh tồn kho thật).
     *
     * Vì Serial/Lot được tạo (firstOrCreate) ngay tại bước LƯU phiếu (Draft),
     * TRƯỚC khi Approve, việc Hủy 1 phiếu Draft coi như "phiếu đó chưa từng
     * tồn tại" về mặt nghiệp vụ — nên phải dọn sạch Lô/Sê-ri nó tạo ra (nếu
     * không còn ai khác dùng), để mã Lô/Sê-ri đó có thể nhập lại ở phiếu
     * khác thay vì bị unique constraint khóa vĩnh viễn.
     *
     * Thứ tự dọn dẹp BẮT BUỘC: Sê-ri trước, Lô sau (cùng lý do với update()).
     *
     * An toàn với FK: cột serial_id/lot_id trên stock_receipt_detail đã đổi
     * sang ON DELETE SET NULL — xóa Serial/Lot không còn có thể gây lỗi FK
     * conflict với các detail của phiếu Cancelled đang tham chiếu. Dữ liệu
     * hiển thị lịch sử của các detail đó vẫn nguyên vẹn nhờ
     * lot_number_snapshot/serial_number_snapshot đã lưu sẵn lúc tạo detail.
     */
    public function cancel(StockReceipt $receipt): void
    {
        if ($receipt->status === DocumentStatus::Completed) {
            throw new \DomainException('Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ($receipt->status === DocumentStatus::Cancelled) {
            throw new \DomainException('Phiếu đã được hủy trước đó.');
        }

        DB::transaction(function () use ($receipt) {
            $lotIds    = $receipt->details()->whereNotNull('lot_id')->pluck('lot_id')->unique()->all();
            $serialIds = $receipt->details()->whereNotNull('serial_id')->pluck('serial_id')->unique()->all();

            $this->receiptRepository->update($receipt, [
                'status' => DocumentStatus::Cancelled->value,
            ]);

            // Dọn Sê-ri TRƯỚC, Lô SAU (xem lý do ở docblock phía trên).
            $this->releaseUnusedSerials($serialIds);
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
     * Xóa các Lô trong $lotIds KHÔNG còn được dùng bởi bất kỳ phiếu ĐANG
     * HOẠT ĐỘNG nào (Draft/Completed — khác Cancelled), chưa gắn Serial
     * nào, và chưa phát sinh tồn kho thật (bảng `stocks`) — để số Lô
     * (lot_number) có thể được tái sử dụng cho phiếu tiếp theo thay vì
     * luôn tăng dần.
     *
     * Detail thuộc phiếu Cancelled KHÔNG được tính là "còn dùng" — dù bản
     * ghi detail đó vẫn tồn tại vật lý trong DB (cancel() không xóa detail,
     * chỉ đổi status header), nó không đại diện cho tồn kho thật (chưa
     * từng Approve, và phiếu Cancelled không bao giờ Approve lại). Việc
     * xóa Lô/Serial vẫn AN TOÀN về mặt kỹ thuật nhờ FK đã đổi sang
     * ON DELETE SET NULL (xem migration stock_receipt_detail).
     *
     * KHÔNG cần loại trừ theo "phiếu đang sửa/hủy" bằng $receipt nữa (khác
     * các phiên bản trước) — hàm này LUÔN chạy SAU khi dữ liệu mới nhất đã
     * ghi (replaceDetails() cho update(), hoặc status đã đổi Cancelled cho
     * cancel()), nên trạng thái đọc trực tiếp từ DB luôn phản ánh đúng thực
     * tế hiện tại, không cần phân biệt phiếu nào.
     *
     * BẮT BUỘC gọi releaseUnusedSerials() TRƯỚC hàm này trong cùng 1 lượt
     * dọn dẹp — vì điều kiện $usedBySerial bên dưới sẽ luôn true (chặn xóa
     * Lô) nếu Serial cũ của chính Lô đó chưa được dọn trước.
     *
     * @param array $lotIds Danh sách lot_id cần kiểm tra, dọn nếu an toàn.
     */
    private function releaseUnusedLots(array $lotIds): void
    {
        if (empty($lotIds)) {
            return;
        }

        foreach ($lotIds as $lotId) {
            $usedByActiveReceiptDetail = StockReceiptDetail::where('stock_receipt_detail.lot_id', $lotId)
                ->join('stock_receipt_line', 'stock_receipt_line.id', '=', 'stock_receipt_detail.stock_receipt_line_id')
                ->join('stock_receipt', 'stock_receipt.id', '=', 'stock_receipt_line.stock_receipt_id')
                ->where('stock_receipt.status', '!=', DocumentStatus::Cancelled->value)
                ->exists();

            $usedByActiveIssueDetail = StockIssueDetail::where('stock_issue_detail.lot_id', $lotId)
                ->join('stock_issue_line', 'stock_issue_line.id', '=', 'stock_issue_detail.stock_issue_line_id')
                ->join('stock_issue', 'stock_issue.id', '=', 'stock_issue_line.stock_issue_id')
                ->where('stock_issue.status', '!=', DocumentStatus::Cancelled->value)
                ->exists();

            $usedByCheckDetail  = DB::table('inventory_check_detail')->where('lot_id', $lotId)->exists();
            $usedByAdjustDetail = DB::table('stock_adjustment_details')->where('lot_id', $lotId)->exists();
            $usedByStock        = Stock::where('lot_id', $lotId)->exists();
            // Chỉ còn true nếu releaseUnusedSerials() đã chạy trước và vẫn
            // còn Serial của Lô này (tức Serial đó đang cần thiết ở nơi
            // khác) — lúc đó Lô này ĐÚNG LÀ vẫn cần giữ lại.
            $usedBySerial = Serial::where('lot_id', $lotId)->exists();

            $stillUsed = $usedByActiveReceiptDetail || $usedByActiveIssueDetail || $usedByCheckDetail
                || $usedByAdjustDetail || $usedByStock || $usedBySerial;

            if (! $stillUsed) {
                Lot::where('id', $lotId)->delete();
            }
        }
    }

    /**
     * Xóa các Sê-ri trong $serialIds KHÔNG còn được dùng bởi bất kỳ phiếu
     * ĐANG HOẠT ĐỘNG nào (khác Cancelled), và chưa phát sinh tồn kho thật.
     *
     * PHẢI được gọi SAU khi replaceDetails() (update) hoặc sau khi header đã
     * chuyển Cancelled (cancel) — để đảm bảo trạng thái "còn dùng hay không"
     * phản ánh đúng dữ liệu MỚI NHẤT, không phải dữ liệu trước khi sửa.
     *
     * Phải gọi hàm này TRƯỚC releaseUnusedLots() trong cùng 1 lượt dọn dẹp.
     *
     * @param array $serialIds Danh sách serial_id (chốt TRƯỚC khi sửa/hủy) cần kiểm tra.
     */
    private function releaseUnusedSerials(array $serialIds): void
    {
        if (empty($serialIds)) {
            return;
        }

        foreach ($serialIds as $serialId) {
            if ($this->isSerialSafeToRelease($serialId)) {
                Serial::where('id', $serialId)->delete();
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
     * ĐANG SỬA vẫn còn tồn tại trong DB (replaceDetails() chưa xóa) — phải
     * loại trừ chúng ra khi kiểm tra "có dòng nào khác đang dùng Lô này",
     * nếu không sẽ luôn thấy Lô "đang bị chính mình dùng" và không bao giờ
     * tái sử dụng được. Khác releaseUnusedLots()/isSerialSafeToRelease()
     * (chạy SAU khi dữ liệu mới đã ghi) — hàm này chạy TRƯỚC, nên vẫn cần
     * loại trừ theo $receipt.
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
        // prepareLineRows()), nên detail cũ của CHÍNH phiếu này vẫn còn trong DB.
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
     * Kiểm tra Sê-ri $serialId có AN TOÀN để XÓA CỨNG hay không — an toàn
     * nghĩa là không còn bị tham chiếu bởi detail của bất kỳ phiếu ĐANG
     * HOẠT ĐỘNG nào (Draft/Completed — khác Cancelled), và chưa phát sinh
     * tồn kho thật.
     *
     * Detail thuộc phiếu Cancelled KHÔNG được tính là "còn dùng" — dù bản
     * ghi detail đó vẫn tồn tại vật lý trong DB, nó không đại diện cho tồn
     * kho thật (chưa từng Approve). Nếu không loại trừ, Serial của MỌI
     * phiếu đã Hủy sẽ vĩnh viễn không bao giờ được giải phóng.
     *
     * KHÔNG loại trừ theo "phiếu đang sửa/hủy" bằng $receipt (khác
     * reusableOldLot()) — hàm này LUÔN được gọi SAU khi dữ liệu mới nhất đã
     * ghi (replaceDetails() cho update(), status Cancelled cho cancel()),
     * nên không cần phân biệt phiếu nào: nếu serial được tái sử dụng bởi
     * chính detail MỚI của phiếu đang sửa (Draft), detail đó vẫn được tính
     * "còn dùng" đúng như mong muốn (vì Draft != Cancelled).
     *
     * An toàn với FK: cột serial_id trên stock_receipt_detail/
     * stock_issue_detail đã đổi sang ON DELETE SET NULL — xóa Serial không
     * còn gây lỗi FK conflict với detail của phiếu Cancelled đang tham
     * chiếu; dữ liệu hiển thị lịch sử của chúng vẫn nguyên vẹn nhờ
     * serial_number_snapshot.
     *
     * @param int $serialId Serial cần kiểm tra.
     * @return bool true nếu Sê-ri an toàn để XÓA.
     */
    private function isSerialSafeToRelease(int $serialId): bool
    {
        $usedByActiveReceiptDetail = StockReceiptDetail::where('stock_receipt_detail.serial_id', $serialId)
            ->join('stock_receipt_line', 'stock_receipt_line.id', '=', 'stock_receipt_detail.stock_receipt_line_id')
            ->join('stock_receipt', 'stock_receipt.id', '=', 'stock_receipt_line.stock_receipt_id')
            ->where('stock_receipt.status', '!=', DocumentStatus::Cancelled->value)
            ->exists();

        $usedByActiveIssueDetail = StockIssueDetail::where('stock_issue_detail.serial_id', $serialId)
            ->join('stock_issue_line', 'stock_issue_line.id', '=', 'stock_issue_detail.stock_issue_line_id')
            ->join('stock_issue', 'stock_issue.id', '=', 'stock_issue_line.stock_issue_id')
            ->where('stock_issue.status', '!=', DocumentStatus::Cancelled->value)
            ->exists();

        $usedByCheckDetail  = DB::table('inventory_check_detail')->where('serial_id', $serialId)->exists();
        $usedByAdjustDetail = DB::table('stock_adjustment_details')->where('serial_id', $serialId)->exists();
        $usedByStock        = Stock::where('serial_id', $serialId)->exists();

        $usedElsewhere = $usedByActiveReceiptDetail || $usedByActiveIssueDetail
            || $usedByCheckDetail || $usedByAdjustDetail || $usedByStock;

        return ! $usedElsewhere;
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
        // Lấy TRƯỚC 1 lần, dùng chung cho mọi line — tránh query lặp lại N lần.
        $oldLotIdsOfThisReceipt = $receipt->exists
            ? $receipt->details()->whereNotNull('lot_id')->pluck('lot_id')->unique()->all()
            : [];

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
                'details'      => $this->prepareDetailRows($line, $receipt, $oldLotIdsOfThisReceipt),
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
     * Mỗi detail được ghi kèm SNAPSHOT (lot_number_snapshot,
     * serial_number_snapshot) — bản sao text/số tại thời điểm nhập, KHÔNG
     * phụ thuộc bản ghi lots/serials gốc còn tồn tại hay không. Dùng để
     * trang show hiển thị đúng lịch sử ngay cả sau khi Lô/Sê-ri đã bị dọn
     * dẹp (releaseUnusedLots/releaseUnusedSerials, khi phiếu bị Hủy) —
     * cột lot_id/serial_id lúc đó sẽ tự động về NULL nhờ FK ON DELETE SET
     * NULL, nhưng snapshot vẫn giữ nguyên giá trị hiển thị.
     *
     * @param array        $line    dữ liệu flat của 1 line (đã qua StockReceiptRequest)
     * @param StockReceipt $receipt
     */
    private function prepareDetailRows(array $line, StockReceipt $receipt, array $excludeLotIds = []): array
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
                    // Loại trừ các Lô thuộc CHÍNH phiếu này (kể cả Lô 5 của dòng
                    // A vừa bị xóa) khỏi phép tính MAX — để "6" không bị sinh ra
                    // trong khi "5" sắp không còn được dùng nữa.
                    $generated = $this->generateUniqueLot($productId, $excludeLotIds);
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
        } else {
            // $lotId được truyền thẳng từ client (dòng Edit giữ nguyên Lô cũ,
            // không xóa trắng ô Số Lô) — nhánh trên KHÔNG chạy nên $lotNumber
            // chưa có giá trị. Phải load lại để snapshot luôn đúng, không
            // phụ thuộc việc Lô có bị xóa sau này (phiếu Cancelled) hay không.
            $lotNumber = Lot::where('id', $lotId)->value('lot_number');
        }

        // ── Bảo vệ tính nhất quán product_id ↔ lot_id ──
        // $lotId có thể đến từ 2 nguồn: (a) vừa resolve/tạo mới ở khối trên
        // (luôn đúng product_id vì firstOrCreate() đã ràng buộc theo đúng
        // product_id), hoặc (b) truyền thẳng qua $line['lot_id'] từ client
        // — trường hợp này CHƯA được xác minh, có thể bị giả mạo hoặc sai
        // lệch do lỗi FE. Vì bảng `serials` có cột product_id riêng
        // (denormalized từ lots.product_id để tối ưu unique constraint và
        // truy vấn), PHẢI đảm bảo 2 giá trị này luôn khớp nhau trước khi
        // dùng $productId để tạo Serial — nếu không, product_id lưu trên
        // Serial sẽ sai lệch với product_id thật của Lô nó thuộc về.
        $lotProductId = Lot::where('id', $lotId)->value('product_id');
        if ((int) $lotProductId !== (int) $productId) {
            throw new \DomainException('Lô đã chọn không thuộc vật tư đang nhập ở dòng này.');
        }

        $commonAttrs = [
            'location_id'         => $line['location_id'] ?? null,
            'receiver_id'         => $line['receiver_id'] ?? null,
            'expiry_date'         => $line['expiry_date'] ?? null,
            'sub_warehouse'       => $line['sub_warehouse'] ?? null,
            'note'                => $line['note'] ?? null,
            // Snapshot SỐ Lô (không phải mã "LOx") tại thời điểm nhập — khớp
            // đúng định dạng hiển thị hiện có (cột "Lô" trên UI show/list là
            // số, ví dụ "5", không phải "LO5").
            'lot_number_snapshot' => $lotNumber,
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
            // Trước: Serial::firstOrCreate(['serial_number' => ...], ...) — unique toàn cục.
            // Giờ resolve theo (product_id, serial_number): 2 sản phẩm khác nhau
            // được phép trùng số Sê-ri, cùng 1 sản phẩm thì không.
            $serial = $this->serialRepository->firstOrCreate(
                $productId,
                $serialNumber,
                [
                    'lot_id' => $lotId,
                    'status' => LotSerialStatus::InStock,
                ]
            );

            $rows[] = array_merge($commonAttrs, [
                'lot_id'                 => $lotId,
                'serial_id'              => $serial->id,
                // Snapshot mã Sê-ri tại thời điểm nhập — lưu đúng $serialNumber
                // người dùng nhập (dù về logic luôn khớp $serial->serial_number
                // do firstOrCreate() resolve theo đúng giá trị này, giữ nguyên
                // input gốc tường minh hơn).
                'serial_number_snapshot' => $serialNumber,
                'actual_qty'             => 1,
            ]);
        }

        return $rows;
    }

    /**
     * Sinh số lô + mã lô tự động và đảm bảo không trùng ngay trong cùng 1 phiếu
     * (nhiều dòng cùng để trống Số Lô trong 1 lần submit).
     *
     * $excludeLotIds: các lot_id thuộc CHÍNH phiếu đang sửa (trước khi sửa) —
     * loại trừ khỏi phép tính MAX(lot_number), vì chúng sắp bị thay thế bởi
     * replaceDetails() ngay sau đó. Nếu không loại trừ, số Lô của phiếu đang
     * sửa vẫn được tính vào MAX dù thực chất sắp không còn tồn tại (ví dụ:
     * xóa dòng có Lô 5, tạo dòng mới để trống Lô → phải được số 5 lại, không
     * phải số 6) — đây chính là bug "nhảy số Lô" khi sửa phiếu.
     *
     * lot_code có unique constraint ở DB nên đây là lớp bảo vệ ở tầng service,
     * tránh vi phạm ràng buộc khi generateLotCode() trả cùng 1 giá trị do
     * chưa kịp ghi xuống DB giữa các lần lặp.
     */
    private function generateUniqueLot(int $productId, array $excludeLotIds = []): array
    {
        do {
            $generated = $this->codeGenerator->generateLotCode($productId, 'LO', $excludeLotIds);
        } while (
            Lot::where('lot_number', $generated['number'])
                ->where('product_id', $productId)
                ->whereNotIn('id', $excludeLotIds)
                ->exists()
        );

        return $generated;
    }
}