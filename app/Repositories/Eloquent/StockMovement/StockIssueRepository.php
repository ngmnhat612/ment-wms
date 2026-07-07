<?php

namespace App\Repositories\Eloquent\StockMovement;

use App\Models\StockMovement\StockIssue;
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
            'createdBy', 'approvedBy',
            'details.product.uom',
            'details.location',
            'details.lot',
            'details.serial',
            'details.uom',
            'details.receiver',
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

    public function delete(StockIssue $issue): bool
    {
        return (bool) $issue->delete();
    }

    public function replaceDetails(StockIssue $issue, array $detailRows): void
    {
        $issue->details()->delete();

        foreach ($detailRows as $row) {
            $row['stock_issue_id'] = $issue->id;
            StockIssueDetail::create($row);
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
}