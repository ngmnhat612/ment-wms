<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TrackingType: int
{
    use HasOptions;

    case Lot          = 1;
    case LotAndSerial = 2;

    public function label(): string
    {
        return match($this) {
            self::Lot          => 'Theo lô',
            self::LotAndSerial => 'Theo lô + sê-ri',
        };
    }
}