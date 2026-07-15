<?php

namespace App\Http\Controllers\StockRequest;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\StockRequest\StockInRequest;
use App\Models\StockRequest\StockOutRequest;
use App\Repositories\Contracts\StockRequest\StockInRequestRepositoryInterface;
use App\Repositories\Contracts\StockRequest\StockOutRequestRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockRequestController extends Controller
{
    public function __construct(
        private StockInRequestRepositoryInterface $inRequestRepository,
        private StockOutRequestRepositoryInterface $outRequestRepository,
    ) {}

    public function index(Request $request): View
    {
        // Danh sách gộp cả 2 loại yêu cầu (in + out) → cần quyền xem cả 2.
        Gate::authorize('viewAny', StockInRequest::class);
        Gate::authorize('viewAny', StockOutRequest::class);

        $type    = $request->input('request_type'); // '' | 'in' | 'out'
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);

        $inRequests = $type === 'out'
            ? collect()
            : $this->inRequestRepository->allForMovementList($filters)->map($this->mapInRequest(...));

        $outRequests = $type === 'in'
            ? collect()
            : $this->outRequestRepository->allForMovementList($filters)->map($this->mapOutRequest(...));

        $all = $inRequests->concat($outRequests);

        // + thêm: sort theo cột người dùng chọn (Mã phiếu / Người tạo / Ngày / Ghi chú)
        $all = $this->sortRequests($all, $request->input('sort', ''), $request->input('dir', ''));

        $requests = $this->paginateCollection($all, $request);

        return view('stock-request.index', [
            'requests'       => $requests,
            'totalCount'     => $all->count(),
            'draftCount'     => $all->where('status', DocumentStatus::Draft)->count(),
            'completedCount' => $all->where('status', DocumentStatus::Completed)->count(),
            'cancelledCount' => $all->where('status', DocumentStatus::Cancelled)->count(),
        ]);
    }

    /**
     * Sort collection đã gộp theo cột được chọn từ query string (?sort=...&dir=...).
     * Mặc định (không sort): theo thời gian tạo giảm dần.
     */
    private function sortRequests(Collection $all, ?string $sort, ?string $dir): Collection
    {
        $sort = $sort ?? '';
        $dir  = $dir ?? '';

        $allowedSorts = ['code', 'created_by', 'doc_date', 'note'];

        if ($sort === '' || ! in_array($sort, $allowedSorts, true) || $dir === '') {
            return $all->sortByDesc('created_at')->values();
        }

        $sorted = $all->sortBy(
            fn ($item) => $item->{$sort} ?? '',
            SORT_FLAG_CASE | SORT_STRING,
            $dir === 'desc'
        );

        return $sorted->values();
    }

    private function mapInRequest($stockInRequest): object
    {
        return (object) [
            'id'              => $stockInRequest->id,
            'request_type'    => 'in',
            'code'            => $stockInRequest->code,
            'warehouse_name'  => $stockInRequest->warehouse?->name,
            'created_by'      => $stockInRequest->createdBy?->display_name,
            'created_by_code' => $stockInRequest->createdBy?->employee?->code,
            'doc_date'        => $stockInRequest->request_date,
            'note'            => $stockInRequest->note,
            'status'          => $stockInRequest->status,
            'created_at'      => $stockInRequest->created_at,
        ];
    }

    private function mapOutRequest($stockOutRequest): object
    {
        return (object) [
            'id'              => $stockOutRequest->id,
            'request_type'    => 'out',
            'code'            => $stockOutRequest->code,
            'warehouse_name'  => $stockOutRequest->warehouse?->name,
            'created_by'      => $stockOutRequest->createdBy?->display_name,
            'created_by_code' => $stockOutRequest->createdBy?->employee?->code,
            'doc_date'        => $stockOutRequest->request_date,
            'note'            => $stockOutRequest->note,
            'status'          => $stockOutRequest->status,
            'created_at'      => $stockOutRequest->created_at,
        ];
    }

    /**
     * Phân trang thủ công trên Collection đã gộp — cần thiết vì $inRequests và
     * $outRequests đến từ 2 bảng khác nhau, không thể union ở tầng SQL đơn giản
     * (2 Repository độc lập, đúng theo Rule #3: mỗi domain có Service/Repository riêng).
     */
    private function paginateCollection(Collection $all, Request $request, int $perPage = 20): LengthAwarePaginator
    {
        $page = (int) $request->input('page', 1);
        $items = $all->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $all->count(),
            perPage: $perPage,
            currentPage: $page,
            options: ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}