<?php

namespace App\Http\Controllers\StockRequest;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockRequest\StockOutRequestRequest;
use App\Models\StockRequest\StockOutRequest;
use App\Repositories\Contracts\Master\WarehouseRepositoryInterface;
use App\Repositories\Contracts\Master\EmployeeRepositoryInterface;
use App\Repositories\Contracts\StockRequest\StockOutRequestRepositoryInterface;
use App\Services\StockRequest\StockOutRequestService;
use Illuminate\Support\Facades\Gate;

class StockOutRequestController extends Controller
{
    public function __construct(
        private StockOutRequestService $stockOutRequestService,
        private StockOutRequestRepositoryInterface $stockOutRequestRepository,
        private WarehouseRepositoryInterface $warehouseRepository,
        private EmployeeRepositoryInterface $employeeRepository,
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
            $stockOutRequest = $this->stockOutRequestService->create(
                array_merge(
                    $request->only(['code', 'note']),
                    ['warehouse_id' => $this->warehouseRepository->allActive()->first()?->id]
                ),
                $request->input('details', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stock-out-requests.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return $request->input('action') === 'save_and_new'
            ? redirect()->route('stock-out-requests.create')->with('success', 'Đã tạo phiếu yêu cầu xuất kho thành công.')
            : redirect()->route('stock-out-requests.show', $stockOutRequest)->with('success', 'Đã tạo phiếu yêu cầu xuất kho thành công.');
    }

    public function show(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('view', $stockOutRequest);

        return view('stock-request.out.show', [
            'stockOutRequest' => $this->stockOutRequestRepository->findWithDetails($stockOutRequest->id),
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
            ['stockOutRequest' => $this->stockOutRequestRepository->findWithDetails($stockOutRequest->id)]
        ));
    }

    public function update(StockOutRequestRequest $request, StockOutRequest $stockOutRequest)
    {
        Gate::authorize('update', $stockOutRequest);

        try {
            $this->stockOutRequestService->update(
                $stockOutRequest,
                $request->only(['note']),
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
            $this->stockOutRequestService->delete($stockOutRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-requests.index')->with('error', $e->getMessage());
        }

        return redirect()->route('stock-requests.index')->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    public function complete(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('complete', $stockOutRequest);

        try {
            $this->stockOutRequestService->complete($stockOutRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-out-requests.show', $stockOutRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-out-requests.show', $stockOutRequest)
            ->with('success', "Phiếu {$stockOutRequest->code} đã được hoàn thành.");
    }

    public function cancel(StockOutRequest $stockOutRequest)
    {
        Gate::authorize('cancel', $stockOutRequest);

        try {
            $this->stockOutRequestService->cancel($stockOutRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-out-requests.show', $stockOutRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-out-requests.show', $stockOutRequest)
            ->with('success', "Đã hủy phiếu {$stockOutRequest->code}.");
    }

    /**
     * Dữ liệu dropdown dùng chung cho create()/edit().
     * Lấy qua Repository — không query DB trực tiếp trong Controller (đúng Rule #1).
     */
    private function formData(): array
    {
        return [
            'employees' => $this->employeeRepository->allActive(),
        ];
    }
}