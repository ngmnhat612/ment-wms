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
        $trashed = $this->reorderRuleRepository->findTrashed(
            $data['product_id'],
            $data['warehouse_id']
        );

        if ($trashed) {
            return $this->reorderRuleRepository->restoreAndUpdate($trashed, [
                'employee_id' => $data['employee_id'],
                'min_qty'     => $data['min_qty'],
                'max_qty'     => $data['max_qty'],
                'note'        => $data['note'] ?? null,
                'status'      => $data['status'],
            ]);
        }

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
