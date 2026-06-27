<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InventoryCheckType: int
{
    use HasOptions;
    
    case Quantity   = 1;
    case Location   = 2;
    case Both       = 3;

    public function label(): string
    {
        return match($this) {
            self::Quantity  => 'Kiểm kê số lượng',
            self::Location  => 'Kiểm kê vị trí',
            self::Both      => 'Kiểm kê số lượng + vị trí',
        };
    }
}