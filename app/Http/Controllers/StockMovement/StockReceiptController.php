<?php

namespace App\Http\Controllers\StockMovement;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovement\StockReceiptRequest;
use App\Enums\DocumentStatus;
use App\Models\Master\Brand;
use App\Models\Master\Employee;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\Sn;
use App\Models\Master\Supplier;
use App\Models\Master\Uom;
use App\Models\Master\Warehouse;
use App\Models\StockMovement\StockReceipt;
use App\Repositories\Contracts\StockMovement\StockReceiptRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockMovementFormDataRepositoryInterface;
use App\Repositories\Contracts\StockRequest\StockInRequestRepositoryInterface;
use App\Services\StockMovement\StockReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StockReceiptController extends Controller
{
    public function __construct(
        private StockReceiptService $receiptService,
        private StockReceiptRepositoryInterface $receiptRepository,
        private StockMovementFormDataRepositoryInterface $formDataRepository,
        private StockInRequestRepositoryInterface $inRequestRepository,
    ) {}

    public function create(Request $request)
    {
        Gate::authorize('create', StockReceipt::class);

        $formData = $this->formData();

        $stockInRequestId = $request->query('stock_in_request_id');

        if ($stockInRequestId) {
            $stockInRequest = $this->inRequestRepository->findWithDetails((int) $stockInRequestId);

            if ($stockInRequest) {
                $formData['prefillLines'] = $this->buildPrefillLines($stockInRequest, $formData['products']);
                $formData['prefillStockInRequestId'] = $stockInRequest->id;
                $formData['prefillStockInRequestCode'] = $stockInRequest->code;
            }
        }

        return view('stock-movement.receipt.form', $formData);
    }

    public function store(StockReceiptRequest $request)
    {
        Gate::authorize('create', StockReceipt::class);

        try {
            $receipt = $this->receiptService->create(
                $request->only(['warehouse_id', 'stock_in_request_id', 'code', 'note', 'receipt_date']),
                $request->input('lines', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('receipts.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return $request->input('action') === 'save_and_new'
            ? redirect()->route('receipts.create')->with('success', 'Đã tạo phiếu nhập thành công.')
            : redirect()->route('receipts.show', $receipt)->with('success', 'Đã tạo phiếu nhập thành công.');
    }

    public function show(StockReceipt $receipt)
    {
        Gate::authorize('view', $receipt);

        return view('stock-movement.receipt.show', [
            'receipt' => $this->receiptRepository->findWithDetails($receipt->id),
        ]);
    }

    public function edit(StockReceipt $receipt)
    {
        Gate::authorize('update', $receipt);

        if ($receipt->status !== DocumentStatus::Draft) {
            return redirect()->route('receipts.show', $receipt)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        return view('stock-movement.receipt.form', array_merge(
            $this->formData(),
            ['receipt' => $this->receiptRepository->findWithDetails($receipt->id)]
        ));
    }

    public function update(StockReceiptRequest $request, StockReceipt $receipt)
    {
        Gate::authorize('update', $receipt);

        try {
            $this->receiptService->update(
                $receipt,
                $request->only(['stock_in_request_id', 'note', 'receipt_date']),
                $request->input('lines', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('receipts.edit', $receipt)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Đã cập nhật phiếu {$receipt->code} thành công.");
    }

    public function destroy(StockReceipt $receipt)
    {
        Gate::authorize('delete', $receipt);

        try {
            $code = $receipt->code;
            $this->receiptService->delete($receipt);
        } catch (\DomainException $e) {
            return redirect()->route('stock-movements.index')->with('error', $e->getMessage());
        }

        return redirect()->route('stock-movements.index')->with('success', "Đã xóa phiếu {$code} thành công.");
    }

    public function approve(StockReceipt $receipt)
    {
        Gate::authorize('approve', $receipt);

        try {
            $this->receiptService->approve($receipt);
        } catch (\DomainException $e) {
            return redirect()->route('receipts.show', $receipt)->with('error', $e->getMessage());
        }

        return redirect()->route('receipts.show', $receipt)
            ->with('success', "Phiếu {$receipt->code} đã được duyệt. Tồn kho đã được cập nhật.");
    }

    public function cancel(StockReceipt $receipt)
    {
        Gate::authorize('cancel', $receipt);

        try {
            $this->receiptService->cancel($receipt);
        } catch (\DomainException $e) {
            return redirect()->route('receipts.show', $receipt)->with('error', $e->getMessage());
        }

        return redirect()->route('receipts.show', $receipt)->with('success', "Đã hủy phiếu {$receipt->code}.");
    }

    public function printPdf(StockReceipt $receipt)
    {
        Gate::authorize('view', $receipt);

        return view('stock-movement.receipt.print', [
            'receipt' => $this->receiptRepository->findWithDetails($receipt->id),
        ]);
    }

    /**
     * Chuyển từng dòng của Phiếu yêu cầu nhập (đã Hoàn thành) thành 1 "line"
     * pre-fill cho form Phiếu nhập (create). Chỉ giữ lại các dòng match được
     * vật tư có sẵn trong hệ thống theo product_code — dòng khai báo vật tư
     * mới (new_product_code, chưa tồn tại trong bảng products) sẽ bị bỏ qua,
     * người dùng tự thêm/chọn lại thủ công trên form.
     */
    private function buildPrefillLines($stockInRequest, $products): array
    {
        $lines = [];

        foreach ($stockInRequest->details as $detail) {
            $product = $detail->product_code
                ? $products->firstWhere('code', $detail->product_code)
                : null;

            if (! $product) {
                continue;
            }

            $lines[] = [
                'product_id'   => $product->id,
                'uom_id'       => $product->uom_id,
                'expected_qty' => (float) $detail->quantity,
                'lot_number'   => $detail->lot_number,
            ];
        }

        return $lines;
    }

    /**
     * Dữ liệu dropdown dùng chung cho create()/edit().
     * Lấy qua StockMovementFormDataRepositoryInterface — không query DB
     * trực tiếp trong Controller (đúng Rule #1).
     */
    private function formData(): array
    {
        $products  = $this->formDataRepository->activeProducts();
        $locations = $this->formDataRepository->receivingLocations();
        $employees = $this->formDataRepository->activeEmployees();
        $sns       = $this->formDataRepository->sns();

        return [
            'products'   => $products,
            'suppliers'  => $this->formDataRepository->suppliers(),
            'brands'     => $this->formDataRepository->brands(),
            'locations'  => $locations,
            'uoms'       => $this->formDataRepository->uoms(),
            'warehouses' => $this->formDataRepository->warehouses(),
            'employees'  => $employees,
            'sns'        => $sns,
            'stockInRequests' => $this->formDataRepository->stockInRequests(),

            // Dùng cho JS phía client (rowTemplate() trong form.blade.php)
            'productsJson' => $products->map(fn ($p) => [
                'id'            => $p->id,
                'code'          => $p->code,
                'name'          => $p->name,
                'uom'           => $p->uom?->name,
                'uom_id'        => $p->uom_id,
                'specification' => $p->specification,
                'tracking'      => $p->tracking_type?->value ?? 1,
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