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
            self::InStock  => 'Còn hàng',
            self::Expired  => 'Hết hạn',
            self::Consumed => 'Hết hàng',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::InStock  => 'badge bg-success-subtle text-success border border-success-subtle',
            self::Expired  => 'badge bg-danger-subtle text-danger border border-danger-subtle',
            self::Consumed => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
        };
    }
}