<?php

namespace App\Repositories\Contracts\StockMovement;

use App\Models\StockMovement\StockIssue;
use App\Models\StockMovement\StockIssueDetail;
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

    /**
     * Xóa toàn bộ lines (+ details con qua cascade) và tạo lại từ $lineRows.
     * Mỗi phần tử của $lineRows là 1 line (product_id/uom_id/sn_id/expected_qty/note)
     * kèm khóa 'details' => mảng các detail con đã được resolve sẵn (lot_id/serial_id...).
     */
    public function replaceDetails(StockIssue $issue, array $lineRows): void;

    public function countByStatus(int $status): int;

    public function generateCode(): string;

    public function allForMovementList(array $filters): Collection;

    public function updateDetailActualQty(StockIssueDetail $detail, float $qty): void;

    public function totalCount(): int;
}