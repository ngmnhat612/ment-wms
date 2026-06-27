<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\WarehouseEmployee\StoreWarehouseEmployeeRequest;
use App\Http\Requests\Master\WarehouseEmployee\UpdateWarehouseEmployeeRequest;
use App\Models\Warehouse;
use App\Models\WarehouseEmployee;
use Illuminate\Support\Facades\DB;

class WarehouseEmployeeController extends Controller
{
    /**
     * Gán nhân viên vào kho
     */
    public function store(StoreWarehouseEmployeeRequest $request)
    {
        $this->authorize('create', WarehouseEmployee::class);

        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        DB::transaction(function () use ($request, $warehouse) {
            // Nếu is_primary = true → bỏ is_primary của người cũ trong kho đó
            if ($request->boolean('is_primary')) {
                $warehouse->warehouseEmployees()
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            WarehouseEmployee::create([
                'warehouse_id' => $request->warehouse_id,
                'employee_id'  => $request->employee_id,
                'is_primary'   => $request->boolean('is_primary'),
            ]);
        });

        return redirect()->route('master.warehouse.index')
            ->with('success', 'Đã gán nhân viên vào kho thành công.');
    }

    /**
     * Cập nhật is_primary
     */
    public function update(UpdateWarehouseEmployeeRequest $request, WarehouseEmployee $warehouse_employee)
    {
        $this->authorize('update', $warehouse_employee);

        DB::transaction(function () use ($request, $warehouse_employee) {
            if ($request->boolean('is_primary')) {
                WarehouseEmployee::where('warehouse_id', $warehouse_employee->warehouse_id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $warehouse_employee->update(['is_primary' => $request->boolean('is_primary')]);
        });

        return redirect()->route('master.warehouse.index')
            ->with('success', 'Đã cập nhật phân công kho thành công.');
    }

    /**
     * Hủy gán nhân viên khỏi kho
     */
    public function destroy(WarehouseEmployee $warehouse_employee)
    {
        $this->authorize('delete', $warehouse_employee);

        $warehouse_employee->delete();

        return redirect()->route('master.warehouse.index')
            ->with('success', 'Đã hủy gán nhân viên khỏi kho.');
    }
}
