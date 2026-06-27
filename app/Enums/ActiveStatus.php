<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ActiveStatus: int
{
    use HasOptions;
    
    case Active   = 1;
    case Inactive = 0;

    public function label(): string
    {
        return match($this) {
            self::Active   => 'Đang hoạt động',
            self::Inactive => 'Ngưng hoạt động',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Active   => 'badge bg-success',
            self::Inactive => 'badge bg-secondary',
        };
    }
}