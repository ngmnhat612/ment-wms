<?php

namespace App\Http\Controllers\Inventory;

use App\Models\Inventory\Stock;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    /**
     * Danh sách tồn kho hiện tại theo sản phẩm/vị trí/lô.
     * Các dòng cùng product+location+lot được gộp (SUM số lượng).
     * Sê-ri chi tiết xem qua modal (route inventory.lotSerials).
     * Mặc định sắp xếp theo thời gian tạo giảm dần (mới nhất trước).
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Stock::class);

        $filters = $request->only([
            'show_virtual', 'show_zero', 'search',
            'category_id', 'product_id', 'location_id', 'status',
            'sort', 'dir',
        ]);

        $data = $this->inventoryService->getStockList($filters);
        $options = $this->inventoryService->getFilterOptions();

        return view('inventory.index', array_merge($data, $options));
    }

    /**
     * AJAX: danh sách serial thuộc 1 lô tại 1 vị trí cụ thể (cho modal "Xem chi tiết").
     */
    public function lotSerials(Request $request)
    {
        Gate::authorize('viewAny', Stock::class);

        $request->validate([
            'product_id'  => 'required|integer',
            'location_id' => 'required|integer',
            'lot_id'      => 'nullable|integer',
        ]);

        $serials = $this->inventoryService->getLotSerials(
            $request->integer('product_id'),
            $request->integer('location_id'),
            $request->filled('lot_id') ? $request->integer('lot_id') : null,
        );

        return response()->json([
            'serials' => $serials->map(function ($s) {
                $statusEnum = $s->status instanceof \App\Enums\LotSerialStatus
                    ? $s->status
                    : \App\Enums\LotSerialStatus::tryFrom((int) $s->status);

                return [
                    'serial_number' => $s->serial_number,
                    'status_label'  => $statusEnum?->label() ?? '?',
                    'status_badge'  => $statusEnum?->badgeClass() ?? 'badge bg-secondary-subtle',
                    'quantity'      => (float) $s->quantity,
                ];
            }),
        ]);
    }

    /**
     * Cập nhật nhanh vị trí kho cho 1 lô/sản phẩm tại vị trí hiện tại,
     * không cần tạo phiếu chuyển kho. Set previous_location_id = vị trí cũ.
     */
    public function updateLocation(Request $request)
    {
        Gate::authorize('update', Stock::class);

        $data = $request->validate([
            'product_id'       => 'required|integer',
            'lot_id'           => 'nullable|integer',
            'from_location_id' => 'required|integer',
            'to_location_id'   => 'required|integer|different:from_location_id',
        ]);

        $this->inventoryService->updateLocation($data);

        return redirect()
            ->route('inventory.index', $request->query())
            ->with('success', 'Đã cập nhật vị trí kho.');
    }
}