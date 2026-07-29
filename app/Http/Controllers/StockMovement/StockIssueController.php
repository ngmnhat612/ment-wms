<?php

namespace App\Http\Controllers\StockMovement;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovement\StockIssueRequest;
use App\Enums\DocumentStatus;
use App\Models\StockMovement\StockIssue;
use App\Repositories\Contracts\StockMovement\StockIssueRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockMovementFormDataRepositoryInterface;
use App\Services\StockMovement\StockIssueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockIssueController extends Controller
{
    public function __construct(
        private StockIssueService $issueService,
        private StockIssueRepositoryInterface $issueRepository,
        private StockMovementFormDataRepositoryInterface $formDataRepository,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', StockIssue::class);

        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $issues = $this->issueRepository->paginate($filters);

        return view('stock-movement.issue.index', [
            'issues'         => $issues,
            'totalCount'     => $this->issueRepository->totalCount(),
            'draftCount'     => $this->issueRepository->countByStatus(DocumentStatus::Draft->value),
            'completedCount' => $this->issueRepository->countByStatus(DocumentStatus::Completed->value),
            'cancelledCount' => $this->issueRepository->countByStatus(DocumentStatus::Cancelled->value),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', StockIssue::class);

        return view('stock-movement.issue.form', $this->formData());
    }

    public function store(StockIssueRequest $request)
    {
        Gate::authorize('create', StockIssue::class);

        try {
            $issue = $this->issueService->create(
                $request->only(['warehouse_id', 'stock_out_request_id', 'code', 'note', 'issue_date']),
                $request->input('lines', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('issues.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return $request->input('action') === 'save_and_new'
            ? redirect()->route('issues.create')->with('success', 'Đã tạo phiếu xuất thành công.')
            : redirect()->route('issues.show', $issue)->with('success', 'Đã tạo phiếu xuất thành công.');
    }

    public function show(StockIssue $issue)
    {
        Gate::authorize('view', $issue);

        return view('stock-movement.issue.show', [
            'issue' => $this->issueRepository->findWithDetails($issue->id),
        ]);
    }

    public function edit(StockIssue $issue)
    {
        Gate::authorize('update', $issue);

        if ($issue->status !== DocumentStatus::Draft) {
            return redirect()->route('issues.show', $issue)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        return view('stock-movement.issue.form', array_merge(
            $this->formData(),
            ['issue' => $this->issueRepository->findWithDetails($issue->id)]
        ));
    }

    public function update(StockIssueRequest $request, StockIssue $issue)
    {
        Gate::authorize('update', $issue);

        try {
            $this->issueService->update(
                $issue,
                $request->only(['stock_out_request_id', 'note', 'issue_date']),
                $request->input('lines', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('issues.edit', $issue)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Đã cập nhật phiếu {$issue->code} thành công.");
    }

    public function complete(StockIssue $issue)
    {
        Gate::authorize('complete', $issue);

        try {
            $this->issueService->complete($issue);
        } catch (\DomainException $e) {
            return redirect()->route('issues.show', $issue)->with('error', $e->getMessage());
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Phiếu {$issue->code} hoàn tất. Tồn kho đã được trừ.");
    }

    public function cancel(StockIssue $issue)
    {
        Gate::authorize('cancel', $issue);

        try {
            $this->issueService->cancel($issue);
        } catch (\DomainException $e) {
            return redirect()->route('issues.show', $issue)->with('error', $e->getMessage());
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Đã hủy phiếu {$issue->code}. Hàng đã được trả về trạng thái sẵn sàng.");
    }

    public function printPdf(StockIssue $issue)
    {
        Gate::authorize('view', $issue);

        return view('stock-movement.issue.print', [
            'issue' => $this->issueRepository->findWithDetails($issue->id),
        ]);
    }

    /**
     * AJAX: vị trí + lot/serial có tồn khả dụng theo sản phẩm.
     * Toàn bộ logic (gợi ý, bung serial theo tracking_type...) nằm ở
     * StockIssueService::getAvailableStockForIssue() — Controller chỉ gọi
     * và trả JSON.
     */
    public function stockLocations(Request $request, int $productId)
    {
        Gate::authorize('create', StockIssue::class);
        
        $issueId = $request->integer('issue_id') ?: null;

        return response()->json($this->issueService->getAvailableStockForIssue($productId, $issueId));
    }

    private function formData(): array
    {
        $products  = $this->formDataRepository->activeProducts();
        $locations = $this->formDataRepository->issuingLocations();
        $employees = $this->formDataRepository->activeEmployees();
        $sns       = $this->formDataRepository->sns();

        return [
            'products'   => $products,
            'locations'  => $locations,
            'employees'  => $employees,
            'sns'        => $sns,
            'warehouses' => $this->formDataRepository->warehouses(),
            'lots'       => $this->formDataRepository->lotsInStockGroupedByProduct(),
            'stockOutRequests' => $this->formDataRepository->stockOutRequests(),

            'productsJson' => $products->map(fn ($p) => [
                'id'            => $p->id,
                'code'          => $p->code,
                'name'          => $p->name,
                'uom'           => $p->uom?->name,
                'uom_id'        => $p->uom_id,
                'specification' => $p->specification,
                'stock'         => 0,
                'tracking_type' => $p->tracking_type?->value ?? 1,
            ])->values(),

            'locationsJson' => $locations->map(fn ($l) => [
                'id'   => $l->id,
                'code' => $l->code,
                'name' => $l->name,
            ])->values(),

            'employeesJson' => $employees->map(fn ($e) => [
                'id'   => $e->id,
                'code' => $e->code,
                'name' => $e->name,
            ])->values(),

            'snsJson' => $sns->map(fn ($s) => [
                'id'   => $s->id,
                'code' => $s->code,
            ])->values(),
        ];
    }
}