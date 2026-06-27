<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LotSerialStatus: int
{
    use HasOptions;
    
    case InStock  = 1;
    case Expired  = 2;
    case Consumed = 3;

    public function label(): string
    {
        return match($this) {
            self::InStock  => 'Trong kho',
            self::Expired  => 'Hết hạn',
            self::Consumed => 'Hết hàng',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::InStock  => 'badge bg-success',
            self::Expired  => 'badge bg-warning text-dark',
            self::Consumed => 'badge bg-secondary',
        };
    }
}