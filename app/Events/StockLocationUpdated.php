<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Phát khi vị trí kho của 1 hoặc nhiều dòng stock (cùng product+lot)
 * được cập nhật nhanh từ màn Tồn kho (không qua phiếu chuyển kho).
 */
class StockLocationUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $productId,
        public readonly int $lotId,
        public readonly int $fromLocationId,
        public readonly int $toLocationId,
        public readonly int $affectedRows,
    ) {}
}