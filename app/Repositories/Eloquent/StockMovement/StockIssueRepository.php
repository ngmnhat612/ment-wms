<?php

namespace App\Repositories\Eloquent\StockMovement;

use App\Models\StockMovement\StockIssue;
use App\Models\StockMovement\StockIssueLine;
use App\Models\StockMovement\StockIssueDetail;
use App\Repositories\Contracts\StockMovement\StockIssueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockIssueRepository implements StockIssueRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = StockIssue::query()
            ->with(['createdBy', 'approvedBy'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithDetails(int $id): ?StockIssue
    {
        return StockIssue::with([
            'createdBy', 'approvedBy', 'stockOutRequest',
            'lines.product.uom', 'lines.product.category',
            'lines.uom', 'lines.sn',
            'lines.details.location', 'lines.details.lot',
            'lines.details.serial', 'lines.details.receiver',
        ])->find($id);
    }

    public function create(array $headerData): StockIssue
    {
        return StockIssue::create($headerData);
    }

    public function update(StockIssue $issue, array $headerData): StockIssue
    {
        $issue->update($headerData);
        return $issue->fresh();
    }

    /**
     * Xóa cứng toàn bộ lines cũ rồi tạo lại mới, dùng khi sửa phiếu Draft
     * (StockIssueService::update()). FK stock_issue_detail.stock_issue_line_id
     * là cascade -> xóa lines là đủ, DB tự cascade xóa details con, không
     * cần xóa details thủ công trước.
     */
    public function replaceDetails(StockIssue $issue, array $lineRows): void
    {
        $issue->lines()->delete();

        foreach ($lineRows as $line) {
            $detailRows = $line['details'] ?? [];
            unset($line['details']);

            $line['stock_issue_id'] = $issue->id;
            $createdLine = StockIssueLine::create($line);

            foreach ($detailRows as $detail) {
                $detail['stock_issue_line_id'] = $createdLine->id;
                StockIssueDetail::create($detail);
            }
        }
    }

    public function countByStatus(int $status): int
    {
        return StockIssue::where('status', $status)->count();
    }

    public function generateCode(): string
    {
        $prefix = 'XK-' . now()->format('Ym') . '-';
        $last = StockIssue::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function allForMovementList(array $filters): Collection
    {
        $query = StockIssue::query()
            ->with(['createdBy', 'approvedBy', 'stockOutRequest'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->get();
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                ->orWhereHas('stockOutRequest', function ($r) use ($search) {
                    $r->where('code', 'like', "%{$search}%");
                });
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }
    }

    public function updateDetailActualQty(StockIssueDetail $detail, float $qty): void
    {
        $detail->update(['actual_qty' => $qty]);
    }

    public function totalCount(): int
    {
        return StockIssue::count();
    }

    /**
     * Tổng reserved_qty mà CHÍNH phiếu $issueId (Draft) đang giữ chỗ, gộp theo
     * (location_id, lot_id) — dùng để loại trừ khỏi available_qty khi tính gợi ý
     * tồn kho lúc SỬA phiếu, tránh trừ đúp phần tự giữ chỗ của chính nó.
     *
     * @return array<string, float> key = "{location_id}:{lot_id}"
     */
    public function reservedQtyByLotLocation(int $issueId, int $productId): array
    {
        $lines = StockIssueLine::where('stock_issue_id', $issueId)
            ->where('product_id', $productId)
            ->with('details')
            ->get();

        $result = [];

        foreach ($lines as $line) {
            $details = $line->details;
            $isSerialTracked = $details->count() > 1 || $details->first()?->serial_id;

            foreach ($details as $detail) {
                if (! $detail->location_id || ! $detail->lot_id) continue;

                $qty = $isSerialTracked
                    ? (float) ($detail->actual_qty ?: 1)
                    : (float) $line->expected_qty;

                $key = $detail->location_id . ':' . $detail->lot_id;
                $result[$key] = ($result[$key] ?? 0) + $qty;
            }
        }

        return $result;
    }
}