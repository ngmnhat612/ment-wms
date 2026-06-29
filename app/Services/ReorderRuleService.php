<?php

namespace App\Services;

use App\Models\ReorderRule;
use App\Repositories\Contracts\ReorderRuleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReorderRuleService
{
    public function __construct(
        private readonly ReorderRuleRepositoryInterface $reorderRuleRepository,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->reorderRuleRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->reorderRuleRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->reorderRuleRepository->activeCount();
    }

    // ===== WRITE =====

    public function create(array $data): ReorderRule
    {
        return $this->reorderRuleRepository->create($data);
    }

    public function update(ReorderRule $rule, array $data): ReorderRule
    {
        $this->reorderRuleRepository->update($rule, $data);

        return $rule->fresh();
    }

    public function delete(ReorderRule $rule): void
    {
        $this->reorderRuleRepository->delete($rule);
    }
}