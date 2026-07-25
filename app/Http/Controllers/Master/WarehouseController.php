<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Master\Warehouse\UpdateWarehouseRequest;
use App\Models\Master\Warehouse;
use App\Services\Master\WarehouseService;
use Illuminate\Http\JsonResponse;
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

        // Chặn input tìm kiếm quá dài (phòng trường hợp gọi trực tiếp qua URL,
        // bỏ qua giới hạn maxlength phía frontend)
        foreach (['search'] as $key) {
            if (isset($filters[$key])) {
                $filters[$key] = mb_substr($filters[$key], 0, 200);
            }
        }

        $warehouses  = $this->warehouseService->search($filters);
        $totalCount  = $this->warehouseService->totalCount();
        $activeCount = $this->warehouseService->activeCount();
        $employees   = $this->warehouseService->activeEmployees();

        return view('master.warehouse.index', compact(
            'warehouses', 'totalCount', 'activeCount', 'employees'
        ));
    }

    // ===== STORE =====

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        Gate::authorize('create', Warehouse::class);

        try {
            $this->warehouseService->create($request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.warehouse.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.warehouse.index')
            ->with('success', "Đã thêm kho \"{$request->name}\" thành công.");
    }

    // ===== UPDATE =====

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('update', $warehouse);

        try {
            $this->warehouseService->update($warehouse, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.warehouse.index')
                ->with('error', $e->getMessage());
        }

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

    // ===== CHECK CODE (AJAX, dùng lúc blur ở form Thêm/Sửa) =====

    public function checkCode(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Warehouse::class);

        $request->validate([
            'code'       => 'required|string|max:20',
            'exclude_id' => 'nullable|integer',
        ]);

        $exists = $this->warehouseService->codeExists(
            $request->string('code')->toString(),
            $request->integer('exclude_id') ?: null
        );

        return response()->json(['exists' => $exists]);
    }
}
