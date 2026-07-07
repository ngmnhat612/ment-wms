<?php

namespace App\Services\StockMovement;

use App\Enums\DocumentStatus;
use App\Models\StockMovement\StockIssue;
use App\Repositories\Contracts\StockMovement\StockIssueRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\StockService;

class StockIssueService
{
    public function __construct(
        private StockIssueRepositoryInterface $issueRepository,
        private StockService $stockService,
    ) {}

    public function create(array $header, array $detailRows): StockIssue
    {
        return DB::transaction(function () use ($header, $detailRows) {
            $issue = $this->issueRepository->create([
                'warehouse_id' => $header['warehouse_id'],
                'code'         => $header['code'] ?: $this->issueRepository->generateCode(),
                'created_by'   => Auth::id(),
                'status'       => DocumentStatus::Draft->value,
                'note'         => $header['note'] ?? null,
                'issue_date'   => $header['issue_date'],
            ]);

            $this->issueRepository->replaceDetails($issue, $this->prepareDetailRows($detailRows));

            return $issue;
        });
    }

    public function update(StockIssue $issue, array $header, array $detailRows): StockIssue
    {
        $this->assertDraft($issue, 'chỉnh sửa');

        return DB::transaction(function () use ($issue, $header, $detailRows) {
            $this->issueRepository->update($issue, [
                'note'       => $header['note'] ?? null,
                'issue_date' => $header['issue_date'],
            ]);

            $this->issueRepository->replaceDetails($issue, $this->prepareDetailRows($detailRows));

            return $issue->fresh();
        });
    }

    public function delete(StockIssue $issue): void
    {
        $this->assertDraft($issue, 'xóa');
        $this->issueRepository->delete($issue);
    }

    /**
     * Draft → Completed. Bước DUY NHẤT trừ tồn kho (qua StockService),
     * đúng Rule #4 (không cập nhật tồn kho trực tiếp) và Rule #3 (qua Service Layer).
     * Gộp approve() + complete() cũ thành một bước duy nhất vì bỏ trạng thái Approved
     * (tương tự StockReceiptService::complete()). Vẫn dùng gợi ý FEFO/FIFO qua
     * StockService::suggestStockForIssue() để chọn đúng lô/vị trí trước khi trừ tồn.
     */
    public function complete(StockIssue $issue): void
    {
        if ($issue->status !== DocumentStatus::Draft->value) {
            throw new \DomainException('Chỉ có thể hoàn tất phiếu đang ở trạng thái Nháp.');
        }

        if ($issue->details()->count() === 0) {
            throw new \DomainException('Phiếu chưa có hàng hóa. Vui lòng thêm ít nhất một dòng.');
        }

        $issue->load('details.product');

        DB::transaction(function () use ($issue) {
            foreach ($issue->details as $detail) {
                $qty = (float) ($detail->actual_qty ?: $detail->expected_qty);
                if ($qty <= 0) continue;

                $remaining   = $qty;
                $suggestions = $this->stockService->suggestStockForIssue(
                    $detail->product_id,
                    $remaining,
                    $detail->location_id
                );

                foreach ($suggestions as $s) {
                    if ($remaining <= 0.0001) break;

                    $take = min($s['qty_suggest'], $remaining);

                    $this->stockService->decrease([
                        'warehouse_id' => $issue->warehouse_id,
                        'product_id'   => $detail->product_id,
                        'location_id'  => $s['location_id'],
                        'quantity'     => $take,
                        'lot_id'       => $s['lot_id'],
                        'serial_id'    => $s['serial_id'],
                    ]);

                    $remaining -= $take;
                }

                if ($remaining > 0.001) {
                    throw new \DomainException(
                        "Không đủ tồn kho để xuất sản phẩm ID {$detail->product_id}. Còn thiếu: {$remaining}."
                    );
                }

                $detail->update(['actual_qty' => $qty]);
            }

            $this->issueRepository->update($issue, [
                'status'      => DocumentStatus::Completed->value,
                'approved_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Hủy phiếu. Với luồng 3 trạng thái (Draft -> Completed -> Cancelled),
     * chỉ Draft mới được hủy — Draft chưa từng đụng tới tồn kho thật nên
     * không cần release/hoàn trả gì cả (khác bản cũ có trạng thái Approved
     * phải release reserved_qty trước khi hủy).
     */
    public function cancel(StockIssue $issue): void
    {
        if ($issue->status === DocumentStatus::Completed->value) {
            throw new \DomainException('Không thể hủy phiếu đã hoàn thành. Vui lòng tạo phiếu điều chỉnh.');
        }

        if ($issue->status === DocumentStatus::Cancelled->value) {
            throw new \DomainException('Phiếu đã được hủy trước đó.');
        }

        $this->issueRepository->update($issue, [
            'status' => DocumentStatus::Cancelled->value,
        ]);
    }

    private function assertDraft(StockIssue $issue, string $action): void
    {
        if ($issue->status !== DocumentStatus::Draft->value) {
            throw new \DomainException("Chỉ có thể {$action} phiếu ở trạng thái Nháp.");
        }
    }

    private function prepareDetailRows(array $detailRows): array
    {
        $rows = [];

        foreach ($detailRows as $row) {
            if (empty($row['product_id']) || empty($row['expected_qty'])) {
                continue;
            }

            $rows[] = [
                'product_id'    => $row['product_id'],
                'uom_id'        => $row['uom_id'],
                'lot_id'        => $row['lot_id'] ?? null,
                'serial_id'     => $row['serial_id'] ?? null,
                'location_id'   => $row['location_id'],
                'expected_qty'  => $row['expected_qty'],
                'actual_qty'    => $row['actual_qty'] ?? null,
                'note'          => $row['note'] ?? null,
                'sn_id'         => $row['sn_id'] ?? null,
                'sub_warehouse' => $row['sub_warehouse'] ?? null,
                'receiver_id'   => $row['receiver_id'] ?? null,
            ];
        }

        return $rows;
    }
}