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
     * Xóa MỀM phiếu (StockIssue dùng SoftDeletes từ migration 000098) —
     * $issue->delete() giờ chỉ set deleted_at, không xóa cứng khỏi DB.
     */
    public function delete(StockIssue $issue): bool
    {
        return (bool) $issue->delete();
    }

    /**
     * Xóa MỀM toàn bộ lines/details cũ rồi tạo lại mới, dùng khi sửa phiếu
     * Draft (StockIssueService::update()). Trước migration 000098, bảng này
     * chưa có deleted_at nên $issue->lines()->delete() là XÓA CỨNG; giờ
     * StockIssueLine/StockIssueDetail đã dùng SoftDeletes nên delete() ở
     * đây chỉ set deleted_at — nhưng Eloquent KHÔNG tự cascade soft delete
     * xuống quan hệ con, nên phải soft-delete details TRƯỚC, rồi mới
     * soft-delete lines (đối xứng với FK 'no action' ở migration 000098b —
     * DB không còn tự cascade cứng nữa).
     */
    public function replaceDetails(StockIssue $issue, array $lineRows): void
    {
        $lineIds = $issue->lines()->pluck('id');
        StockIssueDetail::whereIn('stock_issue_line_id', $lineIds)->delete(); // soft delete
        $issue->lines()->delete(); // soft delete

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
            ->with(['createdBy', 'approvedBy'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->get();
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('code', 'like', "%{$search}%");
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
}