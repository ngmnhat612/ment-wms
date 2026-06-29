<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Master\Warehouse\UpdateWarehouseRequest;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Warehouse::class);

        $filters = $request->only(['search', 'status', 'sort', 'dir']);

        $warehouses  = $this->warehouseService->search($filters);
        $totalCount  = $this->warehouseService->totalCount();
        $activeCount = $this->warehouseService->activeCount();
        $employees   = Employee::where('status', 1)->orderBy('name')->get();

        return view('master.warehouse.index', compact(
            'warehouses', 'totalCount', 'activeCount', 'employees'
        ));
    }

    // ===== STORE =====

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Warehouse::class);

        $this->warehouseService->create($request->validated());

        return redirect()
            ->route('master.warehouse.index')
            ->with('success', "Đã thêm kho \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('update', $warehouse);

        $this->warehouseService->update($warehouse, $request->validated());

        return redirect()
            ->route('master.warehouse.index')
            ->with('success', "Đã cập nhật kho \"{$warehouse->name}\" thành công.");
    }

    // ===== DESTROY =====

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('delete', $warehouse);

        $name = $warehouse->name;

        try {
            $this->warehouseService->delete($warehouse);
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.warehouse.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.warehouse.index')
            ->with('success', "Đã xóa kho \"{$name}\" thành công.");
    }
}