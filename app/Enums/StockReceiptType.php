<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockReceiptType: int
{
    use HasOptions;
    
    case New    = 1;
    case Return = 2;
    case SP     = 3;
    case SO     = 4;

    public function label(): string
    {
        return match($this) {
            self::New    => 'Hàng mới',
            self::Return => 'Hàng trở về',
            self::SP     => 'Hàng SP (ghép)',
            self::SO     => 'Hàng SO (tái sử dụng)',
        };
    }
}