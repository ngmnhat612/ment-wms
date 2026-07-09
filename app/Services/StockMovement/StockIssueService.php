<?php

namespace App\Services\StockMovement;

use App\Enums\DocumentStatus;
use App\Enums\TrackingType;
use App\Models\Inventory\Serial;
use App\Models\StockMovement\StockIssue;
use App\Repositories\Contracts\Inventory\LotRepositoryInterface;
use App\Repositories\Contracts\Inventory\StockRepositoryInterface;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockIssueRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Inventory\StockService;

class StockIssueService
{
    public function __construct(
        private StockIssueRepositoryInterface $issueRepository,
        private StockService $stockService,
        private ProductRepositoryInterface $productRepository,
        private LotRepositoryInterface $lotRepository,
        private StockRepositoryInterface $stockRepository,
    ) {}

    /**
     * Tạo phiếu xuất mới (luôn ở trạng thái Draft).
     * $lineRows: mảng lines[], mỗi line = 1 hàng UI (FLAT), gồm
     * product_id/uom_id/expected_qty + location_id/receiver_id/actual_qty/
     * lot_number/serial_numbers (chuỗi space-separated nếu là Lô+Sê-ri).
     * prepareLineRows() sẽ tự tách serial_numbers thành nhiều dòng detail con.
     *
     * Ngay khi tạo Draft, mọi dòng vật tư có xác định được lot_id/location_id
     * đều được GIỮ CHỖ (reserved_qty += expected_qty, tức số lượng "Xuất")
     * qua StockService::reserve(), để tồn kho hiển thị đúng là "Đang giữ"
     * thay vì vẫn khả dụng cho phiếu khác. lot_number giờ là BẮT BUỘC ở UI
     * và StockIssueRequest (rule 'required'), nên location_id + lot_id luôn
     * có sẵn ở đây. StockIssueRequest đã validate trước rằng Lô đã chọn
     * (tại Vị trí đã chọn) đủ tồn khả dụng cho expected_qty.
     */
    public function create(array $header, array $lineRows): StockIssue
    {
        return DB::transaction(function () use ($header, $lineRows) {
            $issue = $this->issueRepository->create([
                'warehouse_id'         => $header['warehouse_id'],
                'stock_out_request_id' => $header['stock_out_request_id'] ?? null,
                'code'                 => $header['code'] ?: $this->issueRepository->generateCode(),
                'created_by'           => Auth::id(),
                'status'               => DocumentStatus::Draft->value,
                'note'                 => $header['note'] ?? null,
                'issue_date'           => $header['issue_date'],
            ]);

            $preparedLines = $this->prepareLineRows($lineRows);

            $this->issueRepository->replaceDetails($issue, $preparedLines);

            $this->reserveLines($issue, $preparedLines);

            return $issue;
        });
    }

    /**
     * Cập nhật phiếu Draft. Vì replaceDetails() xóa toàn bộ dòng cũ và tạo lại
     * dòng mới, ta phải RELEASE giữ chỗ cũ (theo dữ liệu hiện có trong DB,
     * trước khi xóa) rồi RESERVE lại theo dữ liệu mới, để reserved_qty luôn
     * khớp với nội dung mới nhất của phiếu.
     */
    public function update(StockIssue $issue, array $header, array $lineRows): StockIssue
    {
        $this->assertDraft($issue, 'chỉnh sửa');

        return DB::transaction(function () use ($issue, $header, $lineRows) {
            $issue->load('lines.details');
            $this->releaseIssue($issue);

            $this->issueRepository->update($issue, [
                'stock_out_request_id' => $header['stock_out_request_id'] ?? null,
                'note'                 => $header['note'] ?? null,
                'issue_date'           => $header['issue_date'],
            ]);

            $preparedLines = $this->prepareLineRows($lineRows);

            $this->issueRepository->replaceDetails($issue, $preparedLines);

            $this->reserveLines($issue, $preparedLines);

            return $issue->fresh();
        });
    }

    /**
     * Xóa phiếu Draft. Phải RELEASE giữ chỗ trước khi xóa dòng, vì phiếu Draft
     * đã giữ chỗ (reserved_qty) ngay từ lúc create()/update().
     */
    public function delete(StockIssue $issue): void
    {
        $this->assertDraft($issue, 'xóa');

        DB::transaction(function () use ($issue) {
            $issue->load('lines.details');
            $this->releaseIssue($issue);
            $this->issueRepository->delete($issue);
        });
    }

    /**
     * Draft → Completed. Bước DUY NHẤT trừ tồn kho (qua StockService).
     * Giờ phải loop 2 tầng: lines -> details, vì product_id/uom_id nằm ở
     * line (cha), còn actual_qty/lot_id/serial_id/location_id nằm ở detail (con).
     * Vẫn dùng gợi ý FEFO/FIFO qua StockService::suggestStockForIssue() để
     * chọn đúng lô/vị trí trước khi trừ tồn (ưu tiên vị trí người dùng chọn).
     *
     * Trước khi decrease() từng dòng, RELEASE giữ chỗ tương ứng (nếu có) —
     * đúng thứ tự release() rồi decrease() như StockService đã định nghĩa,
     * tránh vừa giữ chỗ vừa trừ tồn trùng lặp trên cùng 1 lượng hàng.
     */
    public function complete(StockIssue $issue): void
    {
        if ($issue->status !== DocumentStatus::Draft) {
            throw new \DomainException('Chỉ có thể hoàn tất phiếu đang ở trạng thái Nháp.');
        }

        if ($issue->lines()->count() === 0) {
            throw new \DomainException('Phiếu chưa có hàng hóa. Vui lòng thêm ít nhất một dòng.');
        }

        $issue->load('lines.details', 'lines.product');

        DB::transaction(function () use ($issue) {
            foreach ($issue->lines as $line) {
                $details = $line->details;
                $isSerialTracked = $details->count() > 1 || $details->first()?->serial_id;

                foreach ($details as $detail) {
                    // Số lượng THỰC XUẤT dùng để trừ tồn thật (decrease) — có
                    // thể khác với số lượng đã GIỮ CHỖ lúc Draft (expected_qty).
                    $qty = (float) ($detail->actual_qty ?: $line->expected_qty);
                    if ($qty <= 0) continue;

                    // Bỏ giữ chỗ đã đặt lúc Draft cho đúng dòng stock này —
                    // PHẢI dùng đúng cơ sở đã reserve() (reserveLines()): hàng
                    // Sê-ri theo actual_qty=1/detail, hàng không Sê-ri theo
                    // expected_qty của line, KHÔNG phải $qty ở trên (có thể
                    // khác expected_qty nếu actual_qty được sửa trước Complete).
                    $reservedQty = $isSerialTracked
                        ? (float) ($detail->actual_qty ?: 1)
                        : (float) $line->expected_qty;
                    $this->releaseDetail($issue, $line, $detail, $reservedQty);

                    $remaining   = $qty;
                    $suggestions = $this->stockService->suggestStockForIssue(
                        $line->product_id,
                        $remaining,
                        $detail->location_id
                    );

                    foreach ($suggestions as $s) {
                        if ($remaining <= 0.0001) break;

                        $take = min($s['qty_suggest'], $remaining);

                        $this->stockService->decrease([
                            'warehouse_id' => $issue->warehouse_id,
                            'product_id'   => $line->product_id,
                            'location_id'  => $s['location_id'],
                            'quantity'     => $take,
                            'lot_id'       => $s['lot_id'],
                            'serial_id'    => $s['serial_id'],
                        ]);

                        $remaining -= $take;
                    }

                if ($remaining > 0.001) {
                    $product = $line->product;
                    $productLabel = $product ? "{$product->code} - {$product->name}" : "ID {$line->product_id}";

                    throw new \DomainException(
                        "Không đủ tồn kho để xuất vật tư {$productLabel}. Còn thiếu: {$remaining}."
                    );
                }

                    $this->issueRepository->updateDetailActualQty($detail, $qty);
                }
            }

            $this->issueRepository->update($issue, [
                'status'      => DocumentStatus::Completed->value,
                'approved_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Hủy phiếu. Chỉ Draft mới được hủy. Phiếu Draft đã giữ chỗ (reserved_qty)
     * ngay từ lúc tạo/sửa, nên khi hủy PHẢI release để trả lại tồn khả dụng
     * cho các phiếu khác — không còn đúng như trước (Draft "chưa từng đụng
     * tồn kho" chỉ còn áp dụng cho quantity thật, không áp dụng cho reserved_qty).
     */
    public function cancel(StockIssue $issue): void
    {
        if ($issue->status === DocumentStatus::Completed) {
            throw new \DomainException('Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ($issue->status === DocumentStatus::Cancelled) {
            throw new \DomainException('Phiếu đã được hủy trước đó.');
        }

        DB::transaction(function () use ($issue) {
            $issue->load('lines.details');
            $this->releaseIssue($issue);

            $this->issueRepository->update($issue, [
                'status' => DocumentStatus::Cancelled->value,
            ]);
        });
    }

    private function assertDraft(StockIssue $issue, string $action): void
    {
        if ($issue->status !== DocumentStatus::Draft) {
            throw new \DomainException("Chỉ có thể {$action} phiếu ở trạng thái Nháp.");
        }
    }

    /**
     * Giữ chỗ (reserve) cho toàn bộ dòng CHA (line) vừa được tạo cho 1 phiếu
     * Draft, theo số lượng Xuất (expected_qty) — KHÔNG dùng actual_qty, vì
     * actual_qty chỉ được xác nhận chính xác ở bước Complete. reserved_qty ở
     * giai đoạn Draft phản ánh đúng ý nghĩa "đang giữ chỗ cho số lượng dự kiến
     * xuất", đúng yêu cầu nghiệp vụ: Chọn Vật tư + nhập Xuất + chọn Vị trí ->
     * giữ chỗ.
     *
     * Với hàng theo Lô+Sê-ri, mỗi detail đã tách sẵn actual_qty=1/serial nên
     * tổng theo lot_id đúng bằng expected_qty (đã validate ở StockIssueRequest)
     * — reserve theo TỪNG detail (mỗi serial 1 lần) là đúng.
     * Với hàng không serial, chỉ có 1 detail — reserve theo expected_qty của
     * line (không phải actual_qty, có thể null/khác khi còn Draft).
     *
     * Chỉ reserve được dòng nào xác định đủ location_id + lot_id (bắt buộc để
     * StockService xác định đúng dòng `stocks`); dòng thiếu thông tin (ví dụ
     * chưa chọn vị trí/lô) sẽ được bỏ qua và chỉ giữ chỗ khi hoàn tất chỉnh sửa.
     */
    private function reserveLines(StockIssue $issue, array $preparedLines): void
    {
        foreach ($preparedLines as $line) {
            $details = $line['details'] ?? [];
            if (empty($details)) {
                continue;
            }

            $isSerialTracked = count($details) > 1
                || ! empty($details[0]['serial_id'] ?? null);

            foreach ($details as $detail) {
                if (empty($detail['location_id']) || empty($detail['lot_id'])) {
                    continue;
                }

                // Serial: mỗi detail = 1 serial = 1 đơn vị. Không serial: 1 detail
                // duy nhất, giữ chỗ đúng bằng expected_qty của line (số Xuất).
                $qty = $isSerialTracked
                    ? (float) ($detail['actual_qty'] ?? 1)
                    : (float) ($line['expected_qty'] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                $this->stockService->reserve([
                    'product_id'  => $line['product_id'],
                    'location_id' => $detail['location_id'],
                    'lot_id'      => $detail['lot_id'],
                    'serial_id'   => $detail['serial_id'] ?? null,
                    'quantity'    => $qty,
                ]);
            }
        }
    }

    /**
     * Bỏ giữ chỗ (release) cho toàn bộ dòng detail hiện có của 1 phiếu Draft
     * (dùng khi update/delete/cancel), dựa trên dữ liệu ĐANG có trong DB
     * (quan hệ lines.details đã được load trước khi gọi). Đối xứng với
     * reserveLines(): hàng theo Sê-ri release theo actual_qty=1/detail, hàng
     * không Sê-ri release theo expected_qty của line (khớp với lượng đã
     * reserve() lúc Draft, không phải actual_qty có thể khác/null).
     */
    private function releaseIssue(StockIssue $issue): void
    {
        foreach ($issue->lines as $line) {
            $details = $line->details;
            if ($details->isEmpty()) {
                continue;
            }

            $isSerialTracked = $details->count() > 1 || $details->first()->serial_id;

            foreach ($details as $detail) {
                $qty = $isSerialTracked
                    ? (float) ($detail->actual_qty ?: 1)
                    : (float) $line->expected_qty;

                $this->releaseDetail($issue, $line, $detail, $qty);
            }
        }
    }

    /**
     * Bỏ giữ chỗ cho đúng 1 dòng detail, bỏ qua an toàn nếu thiếu location_id
     * hoặc lot_id (dòng đó chưa từng được reserve() thành công).
     */
    private function releaseDetail(StockIssue $issue, $line, $detail, float $qty): void
    {
        if ($qty <= 0 || empty($detail->location_id) || empty($detail->lot_id)) {
            return;
        }

        $this->stockService->release([
            'product_id'  => $line->product_id,
            'location_id' => $detail->location_id,
            'lot_id'      => $detail->lot_id,
            'serial_id'   => $detail->serial_id,
            'quantity'    => $qty,
        ]);
    }

    /**
     * Tầng CHA: chuẩn hóa từng dòng vật tư (line, = 1 hàng UI).
     */
    private function prepareLineRows(array $lineRows): array
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
                'details'      => $this->prepareDetailRows($line),
            ];
        }

        return $rows;
    }

    /**
     * Tầng CON: resolve lot_id/serial_id THẬT (đã có sẵn trong hệ thống) cho
     * 1 line, TÁCH chuỗi serial_numbers (space-separated) thành nhiều dòng
     * detail — mỗi serial 1 dòng, actual_qty=1/dòng — khi sản phẩm là
     * LotAndSerial. Khác StockReceiptService: KHÔNG tạo mới Lô/Sê-ri, chỉ
     * tìm lô/serial đã tồn tại (vì issue chỉ được xuất hàng đã có tồn kho —
     * đã validate sự tồn tại này ở StockIssueRequest).
     */
    private function prepareDetailRows(array $line): array
    {
        $productId = $line['product_id'];
        $product   = $this->productRepository->findById($productId);

        $lotNumberInput = trim((string) ($line['lot_number'] ?? ''));
        $lotId = null;

        if ($lotNumberInput !== '' && ctype_digit($lotNumberInput)) {
            $lot = $this->lotRepository->findByLotNumber((int) $lotNumberInput, (int) $productId);
            $lotId = $lot?->id;
        }

        $commonAttrs = [
            'location_id'   => $line['location_id'] ?? null,
            'receiver_id'   => $line['receiver_id'] ?? null,
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
        // N dòng detail, mỗi dòng 1 serial (đã có sẵn), actual_qty = 1/dòng.
        $serialNumbers = collect(preg_split('/\s+/', trim((string) ($line['serial_numbers'] ?? ''))))
            ->filter(fn ($s) => $s !== '')
            ->unique()
            ->values();

        $rows = [];
        foreach ($serialNumbers as $serialNumber) {
            $serial = Serial::where('serial_number', $serialNumber)->first();

            $rows[] = array_merge($commonAttrs, [
                'lot_id'     => $serial?->lot_id ?? $lotId,
                'serial_id'  => $serial?->id,
                'actual_qty' => 1,
            ]);
        }

        return $rows;
    }

    /**
     * Tồn khả dụng (vị trí + lô + serial) của 1 sản phẩm, dùng cho AJAX gợi ý
     * Vị trí -> Lô -> Sê-ri ở form Phiếu xuất (StockIssueController::stockLocations()).
     *
     * Đây là NGHIỆP VỤ (quyết định khi nào "bung" 1 dòng tồn theo Lô thành
     * nhiều dòng theo từng Sê-ri, dựa trên tracking_type của sản phẩm), nên
     * đặt ở Service Layer — Controller chỉ gọi và trả JSON, không tự xử lý
     * (Rule #3: mọi nghiệp vụ phải đi qua Service Layer).
     *
     * @return Collection<int, array{
     *     location_id:int, location_code:string, location_name:string,
     *     lot_id:int, lot_number:?string, expiry_date:?string,
     *     serial_id:?int, serial_number:?string, available_qty:float,
     * }>
     */
    public function getAvailableStockForIssue(int $productId, ?int $issueId = null): Collection
    {
        $product  = $this->productRepository->findById($productId);
        $tracking = (int) ($product?->tracking_type?->value ?? TrackingType::Lot->value);

        $stocks = $this->stockRepository->availableForIssue($productId, $issueId);
        $result = collect();

        foreach ($stocks as $s) {
            $baseRow = [
                'location_id'   => $s->current_location_id,
                'location_code' => $s->currentLocation?->code ?? '?',
                'location_name' => $s->currentLocation?->name ?? '',
                'lot_id'        => $s->lot_id,
                'lot_number'    => $s->lot?->lot_number,
                'expiry_date'   => $s->lot?->expiry_date?->format('Y-m-d'),
                'serial_id'     => $s->serial_id,
                'serial_number' => $s->serial?->serial_number,
                // available_qty = quantity - reserved_qty + phần chính phiếu này đang
                // giữ chỗ (sẽ được release và reserve lại đúng số mới khi Lưu) — để
                // người sửa phiếu thấy đúng "khả dụng thật nếu bỏ giữ chỗ cũ", tránh
                // báo "vượt khả dụng" sai với chính giá trị đã lưu trước đó.
                'available_qty' => (float) $s->quantity - (float) $s->reserved_qty + (float) ($s->own_reserved ?? 0),
            ];

            if ($tracking === TrackingType::LotAndSerial->value && $s->lot_id && ! $s->serial_id) {
                $serials = $this->stockRepository->serialsInStock($productId, $s->lot_id);
                if ($serials->isNotEmpty()) {
                    foreach ($serials as $serial) {
                        $result->push(array_merge($baseRow, [
                            'serial_id'     => $serial->id,
                            'serial_number' => $serial->serial_number,
                        ]));
                    }
                    continue;
                }
            }

            $result->push($baseRow);
        }

        return $result->values();
    }
}