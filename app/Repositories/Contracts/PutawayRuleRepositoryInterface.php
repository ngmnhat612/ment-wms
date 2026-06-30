<?php

namespace App\Repositories\Contracts;

use App\Models\PutawayRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PutawayRuleRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function totalCount(): int;

    public function activeCount(): int;

    public function create(array $data): PutawayRule;

    public function update(PutawayRule $rule, array $data): bool;

    public function delete(PutawayRule $rule): bool;

    public function findTrashed(int $warehouseId, ?int $productId, ?int $categoryId): ?PutawayRule;
    
    public function restoreAndUpdate(PutawayRule $rule, array $data): PutawayRule;
}