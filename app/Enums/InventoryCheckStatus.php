<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InventoryCheckStatus: int
{
    use HasOptions;
    
    case Draft      = 1;
    case InProgress = 2;
    case Completed  = 3;
    case Cancelled  = 4;

    public function label(): string
    {
        return match($this) {
            self::Draft      => 'Nháp',
            self::InProgress => 'Đang kiểm kê',
            self::Completed  => 'Hoàn thành',
            self::Cancelled  => 'Đã hủy',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft      => 'badge bg-secondary',
            self::InProgress => 'badge bg-warning text-dark',
            self::Completed  => 'badge bg-success',
            self::Cancelled  => 'badge bg-danger',
        };
    }
}