<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\WarehouseEmployee\StoreWarehouseEmployeeRequest;
use App\Http\Requests\Master\WarehouseEmployee\UpdateWarehouseEmployeeRequest;
use App\Models\Master\WarehouseEmployee;
use App\Services\Master\WarehouseEmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WarehouseEmployeeController extends Controller
{
    public function __construct(
        private readonly WarehouseEmployeeService $warehouseEmployeeService,
    ) {}

    /**
     * Gán nhân viên vào kho
     */
    public function store(StoreWarehouseEmployeeRequest $request): RedirectResponse
    {
        Gate::authorize('create', WarehouseEmployee::class);

        $this->warehouseEmployeeService->assign($request->validated());

        return redirect()->route('master.warehouse.index')
            ->with('success', 'Đã gán nhân viên vào kho thành công.');
    }

    /**
     * Cập nhật is_primary
     */
    public function update(UpdateWarehouseEmployeeRequest $request, WarehouseEmployee $warehouse_employee): RedirectResponse
    {
        Gate::authorize('update', $warehouse_employee);

        $this->warehouseEmployeeService->updatePrimary($warehouse_employee, $request->validated());

        return redirect()->route('master.warehouse.index')
            ->with('success', 'Đã cập nhật phân công kho thành công.');
    }

    /**
     * Hủy gán nhân viên khỏi kho
     */
    public function destroy(WarehouseEmployee $warehouse_employee): RedirectResponse
    {
        Gate::authorize('delete', $warehouse_employee);

        $this->warehouseEmployeeService->unassign($warehouse_employee);

        return redirect()->route('master.warehouse.index')
            ->with('success', 'Đã hủy gán nhân viên khỏi kho.');
    }
}