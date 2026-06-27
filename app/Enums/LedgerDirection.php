<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LedgerDirection: int
{
    use HasOptions;
    
    case In  = 1;
    case Out = 2;

    public function label(): string
    {
        return match($this) {
            self::In  => 'Vào',
            self::Out => 'Ra',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::In  => 'badge bg-success',
            self::Out => 'badge bg-danger',
        };
    }
}