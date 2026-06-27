<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LedgerTransactionType: int
{
    use HasOptions;
    
    case Receipt = 1;
    case Issue   = 2;
    case Adjust  = 3;

    public function label(): string
    {
        return match($this) {
            self::Receipt => 'Nhập kho',
            self::Issue   => 'Xuất kho',
            self::Adjust  => 'Điều chỉnh',
        };
    }
}