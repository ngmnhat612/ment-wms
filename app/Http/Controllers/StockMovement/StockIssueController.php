<?php

namespace App\Http\Controllers\StockMovement;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovement\StockIssueRequest;
use App\Enums\DocumentStatus;
use App\Models\StockMovement\StockIssue;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\Employee;
use App\Models\Master\Warehouse;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Lot;
use App\Repositories\Contracts\StockMovement\StockIssueRepositoryInterface;
use App\Services\StockMovement\StockIssueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Inventory\Serial;

class StockIssueController extends Controller
{
    public function __construct(
        private StockIssueService $issueService,
        private StockIssueRepositoryInterface $issueRepository,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', StockIssue::class);

        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $issues = $this->issueRepository->paginate($filters);

        return view('stock-movement.issue.index', [
            'issues'         => $issues,
            'totalCount'     => StockIssue::count(),
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

        $issue = $this->issueService->create(
            $request->only(['warehouse_id', 'code', 'note', 'issue_date']),
            $request->input('details', [])
        );

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

        if ($issue->status !== DocumentStatus::Draft->value) {
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
                $request->only(['note', 'issue_date']),
                $request->input('details', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('issues.show', $issue)->with('error', $e->getMessage());
        }

        return redirect()->route('issues.show', $issue)
            ->with('success', "Đã cập nhật phiếu {$issue->code} thành công.");
    }

    public function destroy(StockIssue $issue)
    {
        Gate::authorize('delete', $issue);

        try {
            $code = $issue->code;
            $this->issueService->delete($issue);
        } catch (\DomainException $e) {
            return redirect()->route('stock-movements.index')->with('error', $e->getMessage());
        }

        return redirect()->route('stock-movements.index')->with('success', "Đã xóa phiếu {$code} thành công.");
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
     * AJAX: vị trí + lot/serial có tồn khả dụng theo sản phẩm — giữ nguyên
     * hành vi cũ. Về lâu dài nên chuyển query Stock này vào StockRepository,
     * hiện để tạm ở Controller vì thuộc phạm vi "tra cứu", không phải nghiệp vụ.
     */
    public function stockLocations(int $productId)
    {
        $product  = Product::find($productId);
        $tracking = (int) ($product?->tracking_type ?? 1);

        $stocks = Stock::with(['location', 'lot', 'serial'])
            ->where('product_id', $productId)
            ->whereHas('location', fn ($q) => $q->where('type', Location::TYPE_INTERNAL))
            ->where(fn ($q) => $q->where('available_qty', '>', 0)
                ->orWhereRaw('(quantity - reserved_qty) > 0'))
            ->get();

        $result = collect();

        foreach ($stocks as $s) {
            $baseRow = [
                'location_id'   => $s->location_id,
                'location_code' => $s->location?->code ?? '?',
                'location_name' => $s->location?->name ?? '',
                'lot_id'        => $s->lot_id,
                'lot_number'    => $s->lot?->lot_number,
                'expiry_date'   => $s->lot?->expiry_date?->format('Y-m-d'),
                'serial_id'     => $s->serial_id,
                'serial_number' => $s->serial?->serial_number,
            ];

            if ($tracking === Product::TRACKING_LOT_AND_SERIAL && $s->lot_id && ! $s->serial_id) {
                $serials = Serial::where('product_id', $productId)
                    ->where('lot_id', $s->lot_id)
                    ->where('status', Serial::STATUS_INSTOCK)
                    ->get();

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

        return response()->json($result->values());
    }

    private function formData(): array
    {
        $products  = Product::with('uom')->where('status', 1)->orderBy('code')->get();
        $locations = Location::where('type', 1)->orderBy('code')->get();
        $employees = Employee::where('status', 1)->orderBy('name')->get();
        $sns       = \App\Models\Master\Sn::orderBy('code')->get();

        return [
            'products'   => $products,
            'locations'  => $locations,
            'employees'  => $employees,
            'sns'        => $sns,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'lots'       => Lot::inStock()
                ->select('id', 'product_id', 'lot_number', 'expiry_date')
                ->orderBy('lot_number')->get()->groupBy('product_id'),

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