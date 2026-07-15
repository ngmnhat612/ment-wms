<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\ReorderRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReorderRuleRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function totalCount(): int;

    public function activeCount(): int;

    public function create(array $data): ReorderRule;

    public function update(ReorderRule $rule, array $data): bool;

    public function delete(ReorderRule $rule): bool;

    public function findByProductAndWarehouse(int $productId, int $warehouseId): ?ReorderRule;

    public function deleteByProduct(int $productId): void;
}
