<?php

namespace App\Repositories\Eloquent\StockRequest;

use App\Models\StockRequest\StockOutRequest;
use App\Models\StockRequest\StockOutRequestDetail;
use App\Repositories\Contracts\StockRequest\StockOutRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StockOutRequestRepository implements StockOutRequestRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = StockOutRequest::query()
            ->with(['warehouse', 'createdBy.employee'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithDetails(int $id): ?StockOutRequest
    {
        return StockOutRequest::with([
            'warehouse', 'createdBy',
            'details.receiver',
        ])->find($id);
    }

    public function create(array $headerData): StockOutRequest
    {
        return StockOutRequest::create($headerData);
    }

    public function update(StockOutRequest $stockOutRequest, array $headerData): StockOutRequest
    {
        $stockOutRequest->update($headerData);
        return $stockOutRequest->fresh();
    }

    public function delete(StockOutRequest $stockOutRequest): bool
    {
        return (bool) $stockOutRequest->delete();
    }

    /**
     * Xóa toàn bộ details cũ rồi tạo lại mới, dùng khi sửa phiếu Draft
     * (StockOutRequestService::update()).
     */
    public function replaceDetails(StockOutRequest $stockOutRequest, array $detailRows): void
    {
        $stockOutRequest->details()->delete();

        foreach ($detailRows as $detail) {
            $detail['stock_out_request_id'] = $stockOutRequest->id;
            StockOutRequestDetail::create($detail);
        }
    }

    public function countByStatus(int $status): int
    {
        return StockOutRequest::where('status', $status)->count();
    }

    public function generateCode(): string
    {
        $prefix = 'YCX-' . now()->format('Ym') . '-';
        $last = StockOutRequest::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function allForMovementList(array $filters): Collection
    {
        $query = StockOutRequest::query()
            ->with(['warehouse', 'createdBy.employee'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('created_at')->get();
    }

    public function totalCount(): int
    {
        return StockOutRequest::count();
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
