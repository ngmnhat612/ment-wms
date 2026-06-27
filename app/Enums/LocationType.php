<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LocationType: int
{
    use HasOptions;
    
    case Internal = 1;
    case Virtual  = 2;

    public function label(): string
    {
        return match($this) {
            self::Internal => 'Vị trí thực',
            self::Virtual  => 'Vị trí ảo',
        };
    }
}