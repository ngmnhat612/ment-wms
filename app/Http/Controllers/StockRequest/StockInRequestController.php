<?php

namespace App\Http\Controllers\StockRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockRequest\StockInRequestRequest;
use App\Enums\DocumentStatus;
use App\Models\StockRequest\StockInRequest;
use App\Repositories\Contracts\StockRequest\StockInRequestRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockMovementFormDataRepositoryInterface;
use App\Services\StockRequest\StockInRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockInRequestController extends Controller
{
    public function __construct(
        private StockInRequestService $inRequestService,
        private StockInRequestRepositoryInterface $inRequestRepository,
        private StockMovementFormDataRepositoryInterface $formDataRepository,
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
            $stockInRequest = $this->inRequestService->create(
                $request->only(['warehouse_id', 'code', 'note']),
                $request->input('details', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stock-in-requests.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return $request->input('action') === 'save_and_new'
            ? redirect()->route('stock-in-requests.create')->with('success', 'Đã tạo yêu cầu nhập kho thành công.')
            : redirect()->route('stock-in-requests.show', $stockInRequest)->with('success', 'Đã tạo yêu cầu nhập kho thành công.');
    }

    public function show(StockInRequest $stockInRequest)
    {
        Gate::authorize('view', $stockInRequest);

        return view('stock-request.in.show', [
            'stockInRequest' => $this->inRequestRepository->findWithDetails($stockInRequest->id),
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
            ['stockInRequest' => $this->inRequestRepository->findWithDetails($stockInRequest->id)]
        ));
    }

    public function update(StockInRequestRequest $request, StockInRequest $stockInRequest)
    {
        Gate::authorize('update', $stockInRequest);

        try {
            $this->inRequestService->update(
                $stockInRequest,
                $request->only(['warehouse_id', 'note']),
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
            $this->inRequestService->delete($stockInRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-requests.index')->with('error', $e->getMessage());
        }

        return redirect()->route('stock-requests.index')->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    public function complete(StockInRequest $stockInRequest)
    {
        Gate::authorize('complete', $stockInRequest);

        try {
            $this->inRequestService->complete($stockInRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-in-requests.show', $stockInRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-in-requests.show', $stockInRequest)
            ->with('success', "Yêu cầu {$stockInRequest->code} đã hoàn tất.");
    }

    public function cancel(StockInRequest $stockInRequest)
    {
        Gate::authorize('cancel', $stockInRequest);

        try {
            $this->inRequestService->cancel($stockInRequest);
        } catch (\DomainException $e) {
            return redirect()->route('stock-in-requests.show', $stockInRequest)->with('error', $e->getMessage());
        }

        return redirect()->route('stock-in-requests.show', $stockInRequest)
            ->with('success', "Đã hủy yêu cầu {$stockInRequest->code}.");
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
            ])->values(),
        ];
    }
}