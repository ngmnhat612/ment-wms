<?php

namespace App\Repositories\Eloquent\StockRequest;

use App\Models\StockRequest\StockInRequest;
use App\Models\StockRequest\StockInRequestDetail;
use App\Repositories\Contracts\StockRequest\StockInRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockInRequestRepository implements StockInRequestRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = StockInRequest::query()
            ->with(['warehouse', 'createdBy.employee'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithDetails(int $id): ?StockInRequest
    {
        return StockInRequest::with([
            'warehouse', 'createdBy',
            'details.qcEmployee', 'details.requester',
        ])->find($id);
    }

    public function create(array $headerData): StockInRequest
    {
        return StockInRequest::create($headerData);
    }

    public function update(StockInRequest $stockInRequest, array $headerData): StockInRequest
    {
        $stockInRequest->update($headerData);
        return $stockInRequest->fresh();
    }

    public function delete(StockInRequest $stockInRequest): bool
    {
        return (bool) $stockInRequest->delete();
    }

    /**
     * Xóa toàn bộ details cũ rồi tạo lại mới, dùng khi sửa phiếu Draft
     * (StockInRequestService::update()).
     */
    public function replaceDetails(StockInRequest $stockInRequest, array $detailRows): void
    {
        $stockInRequest->details()->delete();

        foreach ($detailRows as $detail) {
            $detail['stock_in_request_id'] = $stockInRequest->id;
            StockInRequestDetail::create($detail);
        }
    }

    public function countByStatus(int $status): int
    {
        return StockInRequest::where('status', $status)->count();
    }

    public function generateCode(): string
    {
        $prefix = 'YCN-' . now()->format('Ym') . '-';
        $last = StockInRequest::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function allForMovementList(array $filters): Collection
    {
        $query = StockInRequest::query()
            ->with(['warehouse', 'createdBy.employee'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->get();
    }

    public function totalCount(): int
    {
        return StockInRequest::count();
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
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }
}
