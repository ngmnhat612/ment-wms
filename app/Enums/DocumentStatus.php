<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Trạng thái dùng chung cho các chứng từ nghiệp vụ kho:
 * phiếu nhập kho, phiếu xuất kho, phiếu điều chỉnh tồn kho.
 *
 * 3 trạng thái: Draft -> Completed -> Cancelled.
 * (Kiểm kê dùng enum riêng, không dùng chung với DocumentStatus.)
 */
enum DocumentStatus: int
{
    use HasOptions;

    case Draft     = 1;
    case Completed = 2;
    case Cancelled = 3;

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Draft     => 'Phiếu đang được soạn thảo, chưa gửi duyệt.',
            self::Completed => 'Đã hoàn thành, tồn kho đã cập nhật.',
            self::Cancelled => 'Đã hủy.',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft     => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
            self::Completed => 'badge bg-success-subtle text-success border border-success-subtle',
            self::Cancelled => 'badge bg-danger-subtle text-danger border border-danger-subtle',
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