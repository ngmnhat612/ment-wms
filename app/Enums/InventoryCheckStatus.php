<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Trạng thái riêng cho phiếu kiểm kê (inventory_check).
 *
 * 4 trạng thái: Draft -> InProgress -> Completed / Cancelled.
 */
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
            self::InProgress => 'Đang trong quá trình',
            self::Completed  => 'Hoàn thành',
            self::Cancelled  => 'Đã hủy',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Draft      => 'Phiếu đang được soạn thảo, chưa bắt đầu kiểm kê.',
            self::InProgress => 'Đang kiểm kê, kho có thể đang bị đóng băng.',
            self::Completed  => 'Đã hoàn thành kiểm kê thực tế.',
            self::Cancelled  => 'Đã hủy.',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft      => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
            self::InProgress => 'badge bg-warning-subtle text-warning border border-warning-subtle',
            self::Completed  => 'badge bg-success-subtle text-success border border-success-subtle',
            self::Cancelled  => 'badge bg-danger-subtle text-danger border border-danger-subtle',
        };
    }

    public static function options(): array
    {
        return array_column(
            array_map(
                fn ($case) => ['value' => $case->value, 'label' => $case->label()],
                self::cases()
            ),
            'label',
            'value'
        );
    }
}