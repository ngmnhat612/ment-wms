<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockRotation: int
{
    use HasOptions;

    case FIFO       = 1;
    case FEFO       = 2;
    case Designated = 3;

    public function label(): string
    {
        return match($this) {
            self::FIFO       => 'FIFO (Nhập trước xuất trước)',
            self::FEFO       => 'FEFO (Hết hạn trước xuất trước)',
            self::Designated => 'Chỉ định',
        };
    }
}