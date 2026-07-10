<?php

namespace App\Http\Controllers\StockRequest;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockRequest\StockInRequestRequest;
use App\Models\StockRequest\StockInRequest;
use App\Repositories\Contracts\Master\WarehouseRepositoryInterface;
use App\Repositories\Contracts\Master\EmployeeRepositoryInterface;
use App\Repositories\Contracts\StockRequest\StockInRequestRepositoryInterface;
use App\Services\StockRequest\StockInRequestService;
use Illuminate\Support\Facades\Gate;

class StockInRequestController extends Controller
{
    public function __construct(
        private StockInRequestService $stockInRequestService,
        private StockInRequestRepositoryInterface $stockInRequestRepository,
        private WarehouseRepositoryInterface $warehouseRepository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function create()
    {
        Gate::authorize('create', StockInRequest::class);

        return view('stock-request.in.form', $this->formData());
    }

    public function store(StockInRequestRequest $request)
    {
        Gate::authorize('create', StockInRequest::class);

        try {
            $stockInRequest = $this->stockInRequestService->create(
                array_merge(
                    $request->only(['code', 'note']),
                    ['warehouse_id' => $this->warehouseRepository->allActive()->first()?->id]
                ),
                $request->input('details', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stock-in-requests.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return $request->input('action') === 'save_and_new'
            ? redirect()->route('stock-in-requests.create')->with('success', 'Đã tạo phiếu yêu cầu nhập kho thành công.')
            : redirect()->route('stock-in-requests.show', $stockInRequest)->with('success', 'Đã tạo phiếu yêu cầu nhập kho thành công.');
    }

    public function show(StockInRequest $stockInRequest)
    {
        Gate::authorize('view', $stockInRequest);

        return view('stock-request.in.show', [
            'stockInRequest' => $this->stockInRequestRepository->findWithDetails($stockInRequest->id),
        ]);
    }

    public function edit(StockInRequest $stockInRequest)
    {
        Gate::authorize('update', $stockInRequest);

        if ($stockInRequest->status !== DocumentStatus::Draft) {
            return redirect()->route('stock-in-requests.show', $stockInRequest)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        return view('stock-request.in.form', array_merge(
            $this->formData(),
            ['stockInRequest' => $this->stockInRequestRepository->findWithDetails($stockInRequest->id)]
        ));
    }

    public function update(StockInRequestRequest $request, StockInRequest $stockInRequest)
    {
        Gate::authorize('update', $stockInRequest);

        try {
            $this->stockInRequestService->update(
                $stockInRequest,
                $request->only(['note']),
                $request->input('details', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stock-in-requests.edit', $stockInRequest)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stock-in-requests.show', $stockInRequest)
            ->with('success', "Đã cập nhật phiếu {$stockInRequest->code} thành công.");
    }

    public function destroy(StockInRequest $stockInRequest)
    {
        Gate::authorize('delete', $stockInRequest);

        try {
            $code = $stockInRequest->code;
            $this->stockInRequestService->delete($stockInRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-requests.index')->with('error', $e->getMessage());
        }

        return redirect()->route('stock-requests.index')->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    public function complete(StockInRequest $stockInRequest)
    {
        Gate::authorize('complete', $stockInRequest);

        try {
            $this->stockInRequestService->complete($stockInRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-in-requests.show', $stockInRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-in-requests.show', $stockInRequest)
            ->with('success', "Phiếu {$stockInRequest->code} đã được hoàn thành.");
    }

    public function cancel(StockInRequest $stockInRequest)
    {
        Gate::authorize('cancel', $stockInRequest);

        try {
            $this->stockInRequestService->cancel($stockInRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-in-requests.show', $stockInRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-in-requests.show', $stockInRequest)
            ->with('success', "Đã hủy phiếu {$stockInRequest->code}.");
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