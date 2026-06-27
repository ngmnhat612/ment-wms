<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum StockIssueType: int
{
    use HasOptions;
    
    case Production     = 1;
    case Maintenance    = 2;
    case Borrowing      = 3;
    case ReturnSupplier = 4;

    public function label(): string
    {
        return match($this) {
            self::Production        => 'Sản xuất',
            self::Maintenance       => 'Bảo trì',
            self::Borrowing         => 'Mượn',
            self::ReturnSupplier    => 'Trả nhà cung cấp',
        };
    }
}