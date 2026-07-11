<?php

namespace App\Repositories\Eloquent\Stocktake;

use App\Enums\InventoryCheckScope;
use App\Models\Inventory\Stock;
use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\InventoryCheckDetail;
use App\Repositories\Contracts\Stocktake\InventoryCheckRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryCheckRepository implements InventoryCheckRepositoryInterface
{
    public function paginateForList(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = InventoryCheck::query()
            ->with(['warehouse', 'createdBy.employee', 'activeFreeze'])
            ->withCount('details');

        $this->applyFilters($query, $filters);

        $sort = $filters['sort'] ?? '';
        $dir  = $filters['dir'] ?? '';

        if ($sort && in_array($dir, ['asc', 'desc'], true)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findWithDetails(int $id): ?InventoryCheck
    {
        return InventoryCheck::with([
            'warehouse', 'createdBy.employee',
            'freezes.frozenBy.employee',
            'adjustments.createdBy.employee',
            'details.product', 'details.lot', 'details.uom',
            'details.systemLocation', 'details.actualLocation',
            'details.assignment',
        ])->find($id);
    }

    public function create(array $headerData): InventoryCheck
    {
        return InventoryCheck::create($headerData);
    }

    public function update(InventoryCheck $inventoryCheck, array $headerData): InventoryCheck
    {
        $inventoryCheck->update($headerData);

        return $inventoryCheck->fresh();
    }

    /**
     * Sinh dòng InventoryCheckDetail từ tồn kho hiện có (bảng `stocks`), theo
     * phạm vi đã chọn. actual_qty để NULL (chưa đếm) — khác default 0 của cột
     * DB, vì view phân biệt "chưa kiểm" (NULL) với "đã đếm ra 0" (0).
     */
    public function generateDetailsFromStock(InventoryCheck $inventoryCheck, array $locationIds = []): int
    {
        $query = Stock::query()
            ->where('warehouse_id', $inventoryCheck->warehouse_id)
            ->where('quantity', '>', 0);

        if ($inventoryCheck->check_scope === InventoryCheckScope::ByArea && ! empty($locationIds)) {
            $query->whereIn('current_location_id', $locationIds);
        }

        $rows = $query->get();

        $now = now();

        $inserts = $rows->map(fn (Stock $stock) => [
            'inventory_check_id' => $inventoryCheck->id,
            'product_id'         => $stock->product_id,
            'lot_id'             => $stock->lot_id,
            'serial_id'          => $stock->serial_id,
            'system_location_id' => $stock->current_location_id,
            'actual_location_id' => null,
            'uom_id'             => $stock->product->uom_id,
            'system_qty'         => $stock->quantity,
            'actual_qty'         => null,
            'assignment_id'      => null,
            'note'               => null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ])->all();

        if (! empty($inserts)) {
            InventoryCheckDetail::insert($inserts);
        }

        return count($inserts);
    }

    public function generateCode(): string
    {
        $prefix = 'KK-' . now()->format('Ym') . '-';
        $last = InventoryCheck::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function countByStatus(int $status): int
    {
        return InventoryCheck::where('status', $status)->count();
    }

    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        if (isset($filters['check_scope']) && $filters['check_scope'] !== '') {
            $query->where('check_scope', $filters['check_scope']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('check_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('check_date', '<=', $filters['date_to']);
        }
    }
}
