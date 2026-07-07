<?php

namespace App\Repositories\Contracts\StockMovement;

use App\Models\StockMovement\StockIssue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockIssueRepositoryInterface
{
    /**
     * $filters: search, status, date_from, date_to.
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findWithDetails(int $id): ?StockIssue;

    public function create(array $headerData): StockIssue;

    public function update(StockIssue $issue, array $headerData): StockIssue;

    public function delete(StockIssue $issue): bool;

    public function replaceDetails(StockIssue $issue, array $detailRows): void;

    public function countByStatus(int $status): int;

    public function generateCode(): string;

    public function allForMovementList(array $filters): Collection;
}