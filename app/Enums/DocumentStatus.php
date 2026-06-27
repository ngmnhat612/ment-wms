<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentStatus: int
{
    use HasOptions;
    
    case Draft     = 1;
    case Approved  = 2;
    case Completed = 3;
    case Cancelled = 4;

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Approved  => 'Đã duyệt',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft     => 'badge bg-secondary',
            self::Approved  => 'badge bg-primary',
            self::Completed => 'badge bg-success',
            self::Cancelled => 'badge bg-danger',
        };
    }
}