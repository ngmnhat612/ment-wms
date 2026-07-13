<?php

namespace App\Http\Controllers\StockMovement;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\StockMovement\StockIssue;
use App\Models\StockMovement\StockReceipt;
use App\Repositories\Contracts\StockMovement\StockIssueRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockReceiptRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(
        private StockReceiptRepositoryInterface $receiptRepository,
        private StockIssueRepositoryInterface $issueRepository,
    ) {}

    public function index(Request $request): View
    {
        // Danh sách gộp cả 2 loại chứng từ (receipt + issue) → cần quyền xem cả 2.
        Gate::authorize('viewAny', StockReceipt::class);
        Gate::authorize('viewAny', StockIssue::class);

        $type   = $request->input('movement_type'); // '' | 'receipt' | 'issue'
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);

        $receipts = $type === 'issue'
            ? collect()
            : $this->receiptRepository->allForMovementList($filters)->map($this->mapReceipt(...));

        $issues = $type === 'receipt'
            ? collect()
            : $this->issueRepository->allForMovementList($filters)->map($this->mapIssue(...));

        $all = $receipts->concat($issues);

        // + thêm: sort theo cột người dùng chọn (Mã phiếu / Người tạo / Người duyệt / Ngày / Ghi chú)
        $all = $this->sortMovements($all, $request->input('sort', ''), $request->input('dir', ''));

        $movements = $this->paginateCollection($all, $request);

        return view('stock-movement.index', [
            'movements'      => $movements,
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
    private function sortMovements(Collection $all, ?string $sort, ?string $dir): Collection
    {
        $sort = $sort ?? '';
        $dir  = $dir ?? '';

        $allowedSorts = ['code', 'linked_code', 'created_by', 'approved_by', 'doc_date', 'note'];

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

    private function mapReceipt($receipt): object
    {
        return (object) [
            'id'                 => $receipt->id,
            'movement_type'      => 'receipt',
            'code'               => $receipt->code,
            'linked_id'          => $receipt->stockInRequest?->id,
            'linked_code'        => $receipt->stockInRequest?->code,
            'created_by'         => $receipt->createdBy?->display_name,
            'created_by_code'    => $receipt->createdBy?->employee?->code,
            'approved_by'        => $receipt->approvedBy?->display_name,
            'approved_by_code'   => $receipt->approvedBy?->employee?->code,
            'doc_date'           => $receipt->receipt_date,
            'note'               => $receipt->note,
            'status'             => $receipt->status,
            'created_at'         => $receipt->created_at,
        ];
    }

    private function mapIssue($issue): object
    {
        return (object) [
            'id'                 => $issue->id,
            'movement_type'      => 'issue',
            'code'               => $issue->code,
            'linked_id'          => $issue->stockOutRequest?->id,
            'linked_code'        => $issue->stockOutRequest?->code,
            'created_by'         => $issue->createdBy?->display_name,
            'created_by_code'    => $issue->createdBy?->employee?->code,
            'approved_by'        => $issue->approvedBy?->display_name,
            'approved_by_code'   => $issue->approvedBy?->employee?->code,
            'doc_date'           => $issue->issue_date,
            'note'               => $issue->note,
            'status'             => $issue->status,
            'created_at'         => $issue->created_at,
        ];
    }

    /**
     * Phân trang thủ công trên Collection đã gộp — cần thiết vì $receipts và
     * $issues đến từ 2 bảng khác nhau, không thể union ở tầng SQL đơn giản
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