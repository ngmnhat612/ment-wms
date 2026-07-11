<?php

namespace App\Http\Controllers\Stocktake;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stocktake\InventoryCheckRequest;
use App\Models\Stocktake\InventoryCheck;
use App\Repositories\Contracts\Master\LocationRepositoryInterface;
use App\Repositories\Contracts\Master\WarehouseRepositoryInterface;
use App\Repositories\Contracts\Stocktake\InventoryCheckRepositoryInterface;
use App\Services\Stocktake\InventoryCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InventoryCheckController extends Controller
{
    public function __construct(
        private InventoryCheckService $checkService,
        private InventoryCheckRepositoryInterface $checkRepository,
        private WarehouseRepositoryInterface $warehouseRepository,
        private LocationRepositoryInterface $locationRepository,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', InventoryCheck::class);

        $filters = $request->only(['search', 'check_scope', 'status', 'date_from', 'date_to', 'sort', 'dir']);

        $checks = $this->checkRepository->paginateForList($filters)
            ->through(function (InventoryCheck $check) {
                $check->created_by      = $check->createdBy?->employee?->name;
                $check->created_by_code = $check->createdBy?->employee?->code;
                $check->lines_count     = $check->details_count;
                $check->active_freeze   = $check->activeFreeze;

                return $check;
            });

        return view('stocktake.index', [
            'checks' => $checks,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', InventoryCheck::class);

        return view('stocktake.form', $this->formData());
    }

    public function store(InventoryCheckRequest $request)
    {
        Gate::authorize('create', InventoryCheck::class);

        try {
            $inventoryCheck = $this->checkService->create(
                $request->only(['warehouse_id', 'code', 'check_scope', 'check_type', 'check_date', 'purpose', 'note']),
                $request->input('location_ids', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $inventoryCheck)
            ->with('success', 'Đã tạo phiếu kiểm kê thành công.');
    }

    public function show(InventoryCheck $stocktake): View
    {
        Gate::authorize('view', $stocktake);

        $inventoryCheck = $this->checkRepository->findWithDetails($stocktake->id);

        return view('stocktake.show', [
            'inventoryCheck'  => $inventoryCheck,
            'locationOptions' => $this->locationRepository->allOrdered(),
        ]);
    }

    public function edit(InventoryCheck $stocktake): View|\Illuminate\Http\RedirectResponse
    {
        Gate::authorize('update', $stocktake);

        if ($stocktake->status !== \App\Enums\InventoryCheckStatus::Draft) {
            return redirect()->route('stocktakes.show', $stocktake)
                ->with('error', 'Chỉ có thể chỉnh sửa phiếu ở trạng thái Nháp.');
        }

        return view('stocktake.form', array_merge(
            $this->formData(),
            [
                'inventoryCheck'      => $stocktake,
                'selectedLocationIds' => $stocktake->details()->pluck('system_location_id')->unique()->filter()->values()->all(),
            ]
        ));
    }

    public function update(InventoryCheckRequest $request, InventoryCheck $stocktake)
    {
        Gate::authorize('update', $stocktake);

        try {
            $this->checkService->update(
                $stocktake,
                $request->only(['warehouse_id', 'check_scope', 'check_type', 'check_date', 'purpose', 'note'])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.edit', $stocktake)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Đã cập nhật phiếu {$stocktake->code} thành công.");
    }

    public function start(InventoryCheck $stocktake)
    {
        Gate::authorize('manage', $stocktake);

        try {
            $this->checkService->start($stocktake);
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.show', $stocktake)->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Phiếu {$stocktake->code} đã chuyển sang trạng thái Đang kiểm kê.");
    }

    public function updateDetails(Request $request, InventoryCheck $stocktake)
    {
        Gate::authorize('manage', $stocktake);

        try {
            $this->checkService->updateDetails($stocktake, $request->input('details', []));
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.show', $stocktake)->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', 'Đã lưu số liệu đếm.');
    }

    public function complete(InventoryCheck $stocktake)
    {
        Gate::authorize('manage', $stocktake);

        try {
            $this->checkService->complete($stocktake);
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.show', $stocktake)->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Phiếu {$stocktake->code} đã hoàn thành kiểm kê.");
    }

    public function cancel(InventoryCheck $stocktake)
    {
        Gate::authorize('cancel', $stocktake);

        try {
            $this->checkService->cancel($stocktake);
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.show', $stocktake)->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.show', $stocktake)
            ->with('success', "Đã hủy phiếu {$stocktake->code}.");
    }

    /**
     * Dữ liệu dropdown dùng chung cho create()/edit().
     * Lấy qua Repository — không query DB trực tiếp trong Controller (Rule #1).
     */
    private function formData(): array
    {
        return [
            'warehouses' => $this->warehouseRepository->allActive(),
            'locations'  => $this->locationRepository->allOrdered(),
        ];
    }
}