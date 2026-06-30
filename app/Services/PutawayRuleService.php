<?php

namespace App\Services;

use App\Models\PutawayRule;
use App\Repositories\Contracts\PutawayRuleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PutawayRuleService
{
    public function __construct(
        private readonly PutawayRuleRepositoryInterface $putawayRuleRepository,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->putawayRuleRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->putawayRuleRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->putawayRuleRepository->activeCount();
    }

    // ===== WRITE =====

    public function create(array $data): PutawayRule
    {
        $trashed = $this->putawayRuleRepository->findTrashed(
            $data['warehouse_id'],
            $data['product_id']  ?? null,
            $data['category_id'] ?? null,
        );

        if ($trashed) {
            return $this->putawayRuleRepository->restoreAndUpdate($trashed, [
                'location_id' => $data['location_id'],
                'note'        => $data['note'] ?? null,
                'status'      => $data['status'],
            ]);
        }

        return $this->putawayRuleRepository->create($data);
    }

    public function update(PutawayRule $rule, array $data): PutawayRule
    {
        $this->putawayRuleRepository->update($rule, $data);

        return $rule->fresh();
    }

    public function delete(PutawayRule $rule): void
    {
        $this->putawayRuleRepository->delete($rule);
    }
}
