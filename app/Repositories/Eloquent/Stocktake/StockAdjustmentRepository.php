<?php

namespace App\Repositories\Eloquent\Stocktake;

use App\Models\Stocktake\StockAdjustment;
use App\Models\Stocktake\StockAdjustmentDetail;
use App\Repositories\Contracts\Stocktake\StockAdjustmentRepositoryInterface;

class StockAdjustmentRepository implements StockAdjustmentRepositoryInterface
{
    public function findWithDetails(int $id): ?StockAdjustment
    {
        return StockAdjustment::with([
            'inventoryCheck', 'createdBy.employee', 'approvedBy.employee',
            'details.product', 'details.lot', 'details.uom',
            'details.systemLocation', 'details.actualLocation',
        ])->find($id);
    }

    public function create(array $headerData): StockAdjustment
    {
        return StockAdjustment::create($headerData);
    }

    public function createDetailsFromCheckDetails(StockAdjustment $adjustment, $checkDetails): void
    {
        $now = now();

        $inserts = $checkDetails->map(fn ($detail) => [
            'stock_adjustment_id' => $adjustment->id,
            'check_detail_id'     => $detail->id,
            'product_id'          => $detail->product_id,
            'system_location_id'  => $detail->system_location_id,
            'actual_location_id'  => $detail->actual_location_id,
            'lot_id'              => $detail->lot_id,
            'serial_id'           => $detail->serial_id,
            'uom_id'              => $detail->uom_id,
            'system_qty'          => $detail->system_qty,
            'actual_qty'          => $detail->actual_qty,
            'note'                => null,
            'created_at'          => $now,
            'updated_at'          => $now,
        ])->all();

        if (! empty($inserts)) {
            StockAdjustmentDetail::insert($inserts);
        }
    }

    public function generateCode(): string
    {
        $prefix = 'DC-' . now()->format('Ym') . '-';
        $last = StockAdjustment::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function deleteDetails(StockAdjustment $adjustment): void
    {
        StockAdjustmentDetail::where('stock_adjustment_id', $adjustment->id)->delete();
    }
}
