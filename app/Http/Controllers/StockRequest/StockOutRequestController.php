<?php

namespace App\Http\Controllers\StockRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockRequest\StockOutRequestRequest;
use App\Enums\DocumentStatus;
use App\Models\StockRequest\StockOutRequest;
use App\Repositories\Contracts\StockRequest\StockOutRequestRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockMovementFormDataRepositoryInterface;
use App\Services\StockRequest\StockOutRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockOutRequestController extends Controller
{
    public function __construct(
        private StockOutRequestService $outRequestService,
        private StockOutRequestRepositoryInterface $outRequestRepository,
        private StockMovementFormDataRepositoryInterface $formDataRepository,
    ) {}

    public function create()
    {
        Gate::authorize('create', StockOutRequest::class);

        return view('stock-request.out.form', $this->formData());
    }

    public function store(StockOutRequestRequest $request)
    {
        Gate::authorize('create', StockOutRequest::class);

        try {
            $stockOutRequest = $this->outRequestService->create(
                $request->only(['warehouse_id', 'code', 'note']),
                $request->input('details', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stock-out-requests.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return $request->input('action') === 'save_and_new'
            ? redirect()->route('stock-out-requests.create')->with('success', 'Đã tạo yêu cầu xuất kho thành công.')
            : redirect()->route('stock-out-requests.show', $stockOutRequest)->with('success', 'Đã tạo yêu cầu xuất kho thành công.');
    }

    public function show(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('view', $stockOutRequest);

        return view('stock-request.out.show', [
            'stockOutRequest' => $this->outRequestRepository->findWithDetails($stockOutRequest->id),
        ]);
    }

    public function edit(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('update', $stockOutRequest);

        if ($stockOutRequest->status !== DocumentStatus::Draft) {
            return redirect()->route('stock-out-requests.show', $stockOutRequest)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        return view('stock-request.out.form', array_merge(
            $this->formData(),
            ['stockOutRequest' => $this->outRequestRepository->findWithDetails($stockOutRequest->id)]
        ));
    }

    public function update(StockOutRequestRequest $request, StockOutRequest $stockOutRequest)
    {
        Gate::authorize('update', $stockOutRequest);

        try {
            $this->outRequestService->update(
                $stockOutRequest,
                $request->only(['warehouse_id', 'note']),
                $request->input('details', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stock-out-requests.edit', $stockOutRequest)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stock-out-requests.show', $stockOutRequest)
            ->with('success', "Đã cập nhật phiếu {$stockOutRequest->code} thành công.");
    }

    public function destroy(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('delete', $stockOutRequest);

        try {
            $code = $stockOutRequest->code;
            $this->outRequestService->delete($stockOutRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-requests.index')->with('error', $e->getMessage());
        }

        return redirect()->route('stock-requests.index')->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    public function complete(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('complete', $stockOutRequest);

        try {
            $this->outRequestService->complete($stockOutRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-out-requests.show', $stockOutRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-out-requests.show', $stockOutRequest)
            ->with('success', "Yêu cầu {$stockOutRequest->code} đã hoàn tất.");
    }

    public function cancel(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('cancel', $stockOutRequest);

        try {
            $this->outRequestService->cancel($stockOutRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-out-requests.show', $stockOutRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-out-requests.show', $stockOutRequest)
            ->with('success', "Đã hủy yêu cầu {$stockOutRequest->code}.");
    }

    /**
     * Dữ liệu dropdown dùng chung cho create()/edit().
     * Lấy qua Repository — không query DB trực tiếp trong Controller (Rule #1).
     */
    private function formData(): array
    {
        $employees = $this->formDataRepository->activeEmployees();
        $products  = $this->formDataRepository->activeProducts();

        return [
            'warehouses' => $this->formDataRepository->warehouses(),
            'employees'  => $employees,
            'products'   => $products,

            'employeesJson' => $employees->map(fn ($e) => [
                'id'   => $e->id,
                'code' => $e->code,
                'name' => $e->name,
            ])->values(),

            'productsJson' => $products->map(fn ($p) => [
                'code'          => $p->code,
                'name'          => $p->name,
                'specification' => $p->specification,
                'uom_name'      => $p->uom->name ?? '',
                'tracking_type' => $p->tracking_type?->value ?? 1,
            ])->values(),
        ];
    }
}