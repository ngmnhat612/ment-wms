<?php

namespace App\Repositories\Contracts\Stocktake;

use App\Models\Stocktake\InventoryCheck;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryCheckRepositoryInterface
{
    /**
     * Lấy danh sách phiếu kiểm kê có phân trang, kèm filter.
     * $filters có thể chứa: search, check_scope, status, date_from, date_to.
     * Trả về kèm sẵn các cột phái sinh (created_by, created_by_code, lines_count,
     * active_freeze) để view index không cần N+1 query.
     */
    public function paginateForList(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findWithDetails(int $id): ?InventoryCheck;

    public function create(array $headerData): InventoryCheck;

    public function update(InventoryCheck $inventoryCheck, array $headerData): InventoryCheck;

    /**
     * Sinh sẵn các dòng InventoryCheckDetail dựa trên phạm vi kiểm kê,
     * bằng cách join bảng `stocks` hiện có của kho.
     * $locationIds chỉ dùng khi check_scope = ByArea.
     */
    public function generateDetailsFromStock(InventoryCheck $inventoryCheck, array $locationIds = []): int;

    public function generateCode(): string;

    public function countByStatus(int $status): int;
}
