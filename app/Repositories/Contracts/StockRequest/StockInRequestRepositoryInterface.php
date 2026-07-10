<?php

namespace App\Repositories\Contracts\StockRequest;

use App\Models\StockRequest\StockInRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockInRequestRepositoryInterface
{
    /**
     * $filters: search, status, date_from, date_to.
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findWithDetails(int $id): ?StockInRequest;

    public function create(array $headerData): StockInRequest;

    public function update(StockInRequest $stockInRequest, array $headerData): StockInRequest;

    public function delete(StockInRequest $stockInRequest): bool;

    /**
     * Xóa toàn bộ details cũ (nếu có) và tạo lại từ $detailRows.
     */
    public function replaceDetails(StockInRequest $stockInRequest, array $detailRows): void;

    public function countByStatus(int $status): int;

    public function generateCode(): string;

    public function allForMovementList(array $filters): Collection;

    public function totalCount(): int;
}
