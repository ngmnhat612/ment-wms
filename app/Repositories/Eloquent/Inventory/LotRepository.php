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
}