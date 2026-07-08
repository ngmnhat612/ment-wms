<?php

namespace App\Repositories\Contracts\StockMovement;

use App\Models\StockMovement\StockReceipt;
use App\Models\StockMovement\StockReceiptDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface StockReceiptRepositoryInterface
{
    /**
     * Lấy danh sách phiếu nhập có phân trang, kèm filter.
     * $filters có thể chứa: search, status, date_from, date_to.
     * Lưu ý: receipt_type/supplier_id/reference_no nằm ở DETAIL,
     * nên filter theo các field này phải join sang stock_receipt_detail.
     */

    public function findWithDetails(int $id): ?StockReceipt;

    public function create(array $headerData): StockReceipt;

    public function update(StockReceipt $receipt, array $headerData): StockReceipt;

    public function delete(StockReceipt $receipt): bool;

    public function replaceDetails(StockReceipt $receipt, array $detailRows): void;

    public function countByStatus(int $status): int;

    public function generateCode(): string;

    /**
     * Dùng cho StockMovementController::index() — trả về collection thô
     * (không phân trang) để gộp chung với StockIssue.
     */
    public function allForMovementList(array $filters): Collection;

    /**
     * Cập nhật actual_qty thật sau khi duyệt (approve).
     * Tách riêng khỏi update() header vì đây là field ở tầng detail.
     */
    public function updateDetailActualQty(StockReceiptDetail $detail, float $qty): void;
}