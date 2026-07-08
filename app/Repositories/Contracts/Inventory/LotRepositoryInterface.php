<?php

namespace App\Repositories\Contracts\Inventory;

use App\Models\Inventory\Lot;

interface LotRepositoryInterface
{
    /**
     * Tìm lô theo lot_code (unique toàn hệ thống), tạo mới nếu chưa có.
     * $productId vẫn được truyền vào để lưu trong $attributes/record mới,
     * nhưng KHÔNG còn là điều kiện tìm kiếm — lot_code là duy nhất toàn cục,
     * không phụ thuộc sản phẩm.
     */
    public function firstOrCreate(int $productId, string $lotCode, array $attributes): Lot;

    /**
     * Lấy lot_number (số thứ tự) lớn nhất hiện có trong bảng lots.
     * Dùng để CodeGeneratorService sinh lot_code/lot_number tiếp theo.
     */
    public function maxLotNumber(): int;

    /**
     * Tìm lô theo (lot_number, product_id).
     * QUAN TRỌNG: lot_number CHỈ duy nhất TRONG PHẠM VI 1 sản phẩm (mỗi sản
     * phẩm có dãy số lô riêng, xem Lot::product_id) — KHÔNG được bỏ qua
     * $productId khi tìm, nếu không sẽ có thể trả về lô của SẢN PHẨM KHÁC
     * có cùng lot_number, dẫn đến validate sai hoặc trừ nhầm tồn kho.
     */
    public function findByLotNumber(int $lotNumber, int $productId): ?Lot;
}