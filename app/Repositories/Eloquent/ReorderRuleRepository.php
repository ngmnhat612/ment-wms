<?php

namespace App\Repositories\Eloquent;

use App\Enums\ActiveStatus;
use App\Models\ReorderRule;
use App\Repositories\Contracts\ReorderRuleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReorderRuleRepository implements ReorderRuleRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = ReorderRule::with(['product', 'warehouse', 'employee']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn($p) =>
                    $p->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                )->orWhereHas('warehouse', fn($w) =>
                    $w->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                );
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('reorder_rules.status', $filters['status']);
        }

        $sortDir = in_array($filters['dir'] ?? '', ['asc', 'desc']) ? $filters['dir'] : 'desc';

        if (($filters['sort'] ?? '') === 'product_name') {
            $query->join('products', 'reorder_rules.product_id', '=', 'products.id')
                ->orderBy('products.name', $sortDir)
                ->select('reorder_rules.*');
        } else {
            $sortable = ['min_qty', 'max_qty'];
            $sortBy   = in_array($filters['sort'] ?? '', $sortable) ? $filters['sort'] : 'created_at';
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return ReorderRule::count();
    }

    public function activeCount(): int
    {
        return ReorderRule::where('status', ActiveStatus::Active->value)->count();
    }

    public function create(array $data): ReorderRule
    {
        return ReorderRule::create($data);
    }

    public function update(ReorderRule $rule, array $data): bool
    {
        return $rule->update($data);
    }

    public function delete(ReorderRule $rule): bool
    {
        return $rule->delete();
    }

    public function findTrashed(int $productId, int $warehouseId): ?ReorderRule
    {
        return ReorderRule::withTrashed()
            ->where('product_id',   $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('deleted_at')
            ->first();
    }

    public function restoreAndUpdate(ReorderRule $rule, array $data): ReorderRule
    {
        $rule->restore();
        $rule->update($data);
        return $rule->fresh();
    }
}