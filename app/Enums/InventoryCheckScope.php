<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InventoryCheckScope: int
{
    use HasOptions;
    
    case EntireWarehouse    = 1;
    case ByArea             = 2;

    public function label(): string
    {
        return match($this) {
            self::EntireWarehouse   => 'Toàn kho',
            self::ByArea            => 'Theo khu vực',
        };
    }
}