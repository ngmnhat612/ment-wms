<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\PutawayRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PutawayRuleRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function totalCount(): int;

    public function activeCount(): int;

    public function create(array $data): PutawayRule;

    public function update(PutawayRule $rule, array $data): bool;

    public function delete(PutawayRule $rule): bool;

    public function findByProductAndWarehouse(int $productId, int $warehouseId): ?PutawayRule;

    public function findByCategoryAndWarehouse(int $categoryId, int $warehouseId): ?PutawayRule;
}
