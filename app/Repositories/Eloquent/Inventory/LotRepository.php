<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Models\Inventory\Lot;
use App\Repositories\Contracts\Inventory\LotRepositoryInterface;

class LotRepository implements LotRepositoryInterface
{
    public function firstOrCreate(int $productId, string $lotCode, array $attributes): Lot
    {
        return Lot::firstOrCreate(
            ['lot_code' => $lotCode],
            array_merge(
                ['lot_number' => $this->extractLotNumber($lotCode)],
                $attributes,
                ['product_id' => $productId]
            )
        );
    }

    public function maxLotNumber(): int
    {
        return (int) (Lot::max('lot_number') ?? 0);
    }

    /**
     * Tách phần số từ lot_code (vd "LO12" -> 12) để lưu vào cột lot_number.
     * Trả về 0 nếu không tìm thấy số nào trong mã.
     */
    private function extractLotNumber(string $lotCode): int
    {
        return preg_match('/(\d+)$/', $lotCode, $m) ? (int) $m[1] : 0;
    }

    public function findByLotNumber(int $lotNumber, int $productId): ?Lot
    {
        // BẮT BUỘC lọc theo product_id: lot_number chỉ duy nhất trong phạm vi
        // 1 sản phẩm, không phải toàn hệ thống (2 sản phẩm khác nhau có thể
        // cùng có "Lô số 6"). Bỏ sót điều kiện này từng khiến validate/service
        // lấy nhầm lô của sản phẩm khác khi 2 sản phẩm trùng lot_number.
        return Lot::where('lot_number', $lotNumber)
            ->where('product_id', $productId)
            ->first();
    }
}