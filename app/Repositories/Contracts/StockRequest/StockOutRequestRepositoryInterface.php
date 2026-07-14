<?php

namespace App\Repositories\Contracts\StockRequest;

use App\Models\StockRequest\StockOutRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockOutRequestRepositoryInterface
{
    /**
     * $filters: search, status, date_from, date_to.
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findWithDetails(int $id): ?StockOutRequest;

    public function create(array $headerData): StockOutRequest;

    public function update(StockOutRequest $stockOutRequest, array $headerData): StockOutRequest;

    public function delete(StockOutRequest $stockOutRequest): bool;

    /**
     * Xóa toàn bộ details cũ (nếu có) và tạo lại từ $detailRows.
     */
    public function replaceDetails(StockOutRequest $stockOutRequest, array $detailRows): void;

    public function countByStatus(int $status): int;

    public function generateCode(): string;

    public function allForMovementList(array $filters): Collection;

    public function totalCount(): int;
}
