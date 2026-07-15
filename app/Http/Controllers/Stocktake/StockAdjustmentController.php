<?php

namespace App\Http\Controllers\Stocktake;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stocktake\StockAdjustmentRequest;
use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\StockAdjustment;
use App\Repositories\Contracts\Stocktake\StockAdjustmentRepositoryInterface;
use App\Services\Stocktake\StockAdjustmentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private StockAdjustmentService $adjustmentService,
        private StockAdjustmentRepositoryInterface $adjustmentRepository,
    ) {}

    public function create(InventoryCheck $stocktake): View
    {
        Gate::authorize('create', StockAdjustment::class);

        return view('stocktake.adjustment.form', [
            'inventoryCheck' => $stocktake->load('details.product', 'details.uom', 'details.lot', 'details.systemLocation', 'details.actualLocation'),
        ]);
    }

    public function store(StockAdjustmentRequest $request, InventoryCheck $stocktake)
    {
        Gate::authorize('create', StockAdjustment::class);

        try {
            $adjustment = $this->adjustmentService->create(
                $stocktake,
                $request->only(['adjustment_date', 'note']),
                $request->input('detail_ids', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.adjustment.create', $stocktake)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])
            ->with('success', 'Đã tạo phiếu điều chỉnh thành công.');
    }

    public function show(InventoryCheck $stocktake, StockAdjustment $adjustment): View
    {
        Gate::authorize('view', $adjustment);

        return view('stocktake.adjustment.show', [
            'inventoryCheck' => $stocktake,
            'adjustment'     => $this->adjustmentRepository->findWithDetails($adjustment->id),
        ]);
    }

    public function complete(InventoryCheck $stocktake, StockAdjustment $adjustment)
    {
        Gate::authorize('complete', $adjustment);

        try {
            $this->adjustmentService->complete($adjustment);
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])
            ->with('success', "Phiếu {$adjustment->code} đã được xác nhận. Tồn kho đã được cập nhật.");
    }

    public function cancel(InventoryCheck $stocktake, StockAdjustment $adjustment)
    {
        Gate::authorize('cancel', $adjustment);

        try {
            $this->adjustmentService->cancel($adjustment);
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])
            ->with('success', "Đã hủy phiếu {$adjustment->code}.");
    }

    public function edit(InventoryCheck $stocktake, StockAdjustment $adjustment): View
    {
        Gate::authorize('update', $adjustment);

        return view('stocktake.adjustment.form', [
            'inventoryCheck' => $stocktake->load('details.product', 'details.uom', 'details.lot', 'details.systemLocation', 'details.actualLocation'),
            'adjustment'     => $this->adjustmentRepository->findWithDetails($adjustment->id),
        ]);
    }

    public function update(StockAdjustmentRequest $request, InventoryCheck $stocktake, StockAdjustment $adjustment)
    {
        Gate::authorize('update', $adjustment);

        try {
            $this->adjustmentService->update(
                $adjustment,
                $request->only(['adjustment_date', 'note']),
                $request->input('detail_ids', [])
            );
        } catch (\DomainException $e) {
            return redirect()->route('stocktakes.adjustment.edit', [$stocktake, $adjustment])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('stocktakes.adjustment.show', [$stocktake, $adjustment])
            ->with('success', 'Đã cập nhật phiếu điều chỉnh thành công.');
    }
}
