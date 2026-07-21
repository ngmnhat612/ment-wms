<?php

namespace App\Repositories\Eloquent\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\PutawayRule;
use App\Repositories\Contracts\Master\PutawayRuleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PutawayRuleRepository implements PutawayRuleRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = PutawayRule::with(['product', 'category', 'destinationLocation']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', fn($p) =>
                    $p->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                )->orWhereHas('category', fn($c) =>
                    $c->where('name', 'like', "%{$search}%")
                );
            });
        }

        if (!empty($filters['apply_on'])) {
            if ($filters['apply_on'] === 'product') {
                $query->whereNotNull('putaway_rules.product_id');
            } elseif ($filters['apply_on'] === 'category') {
                $query->whereNotNull('putaway_rules.category_id');
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('putaway_rules.status', $filters['status']);
        }

        $sortDir = in_array($filters['dir'] ?? '', ['asc', 'desc']) ? $filters['dir'] : 'desc';

        if (($filters['sort'] ?? '') === 'applies_on') {
            $query
                ->leftJoin('products',   'putaway_rules.product_id',  '=', 'products.id')
                ->leftJoin('categories', 'putaway_rules.category_id', '=', 'categories.id')
                ->orderBy(\Illuminate\Support\Facades\DB::raw('COALESCE(products.name, categories.name)'), $sortDir)
                ->select('putaway_rules.*');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return PutawayRule::count();
    }

    public function activeCount(): int
    {
        return PutawayRule::where('status', ActiveStatus::Active->value)->count();
    }

    public function create(array $data): PutawayRule
    {
        return PutawayRule::create($data);
    }

    public function update(PutawayRule $rule, array $data): bool
    {
        return $rule->update($data);
    }

    public function delete(PutawayRule $rule): bool
    {
        return $rule->delete();
    }

    public function findByProductAndWarehouse(int $productId, int $warehouseId): ?PutawayRule
    {
        return PutawayRule::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    public function findByCategoryAndWarehouse(int $categoryId, int $warehouseId): ?PutawayRule
    {
        return PutawayRule::where('category_id', $categoryId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }
}
