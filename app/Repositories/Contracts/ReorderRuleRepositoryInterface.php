<?php

namespace App\Repositories\Contracts;

use App\Models\ReorderRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReorderRuleRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function totalCount(): int;

    public function activeCount(): int;

    public function create(array $data): ReorderRule;

    public function update(ReorderRule $rule, array $data): bool;

    public function delete(ReorderRule $rule): bool;

    public function findTrashed(int $productId, int $warehouseId): ?ReorderRule;

    public function restoreAndUpdate(ReorderRule $rule, array $data): ReorderRule;
}