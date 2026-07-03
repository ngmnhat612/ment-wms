<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * Trạng thái dùng chung cho các loại chứng từ trong hệ thống:
 * - 'movement'         : phiếu nhập kho, phiếu xuất kho, phiếu điều chỉnh tồn kho
 * - 'inventory_check'  : phiếu kiểm kê
 *
 * 4 trạng thái (Draft/Approved/Completed/Cancelled) dùng chung 1 bộ value,
 * chỉ khác nhau ở label/description hiển thị tùy theo $context truyền vào,
 * vì cùng value=2 nhưng ý nghĩa nghiệp vụ khác nhau:
 *   - movement:        Approved = "Đã duyệt" (thủ kho đã duyệt, chờ thực hiện)
 *   - inventory_check: Approved = "Đang kiểm kê" (đóng băng đang kích hoạt)
 */
enum DocumentStatus: int
{
    use HasOptions;

    case Draft     = 1;
    case Approved  = 2;
    case Completed = 3;
    case Cancelled = 4;

    /**
     * Nhãn hiển thị ngắn gọn (dùng cho badge).
     *
     * @param  string  $context  'movement' | 'inventory_check'
     */
    public function label(string $context = 'movement'): string
    {
        if ($context === 'inventory_check') {
            return match($this) {
                self::Draft     => 'Nháp',
                self::Approved  => 'Đang kiểm kê',
                self::Completed => 'Hoàn thành',
                self::Cancelled => 'Đã hủy',
            };
        }

        return match($this) {
            self::Draft     => 'Nháp',
            self::Approved  => 'Đã duyệt',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    /**
     * Mô tả chi tiết ý nghĩa của từng trạng thái, dùng cho tooltip,
     * help text trên form, hoặc tài liệu hướng dẫn nghiệp vụ.
     *
     * @param  string  $context  'movement' | 'inventory_check'
     */
    public function description(string $context = 'movement'): string
    {
        if ($context === 'inventory_check') {
            return match($this) {
                self::Draft     => 'Phiếu đang được soạn thảo, chưa bắt đầu kiểm kê.',
                self::Approved  => 'Đang kiểm kê (đóng băng tồn kho đang kích hoạt).',
                self::Completed => 'Đã hoàn thành kiểm kê.',
                self::Cancelled => 'Đã hủy.',
            };
        }

        return match($this) {
            self::Draft     => 'Phiếu đang được soạn thảo, chưa gửi duyệt.',
            self::Approved  => 'Thủ kho đã duyệt, chờ thực hiện.',
            self::Completed => 'Đã hoàn thành, tồn kho đã cập nhật.',
            self::Cancelled => 'Đã hủy.',
        };
    }

    /**
     * Class CSS cho badge (dạng subtle) — dùng chung cho mọi context,
     * khớp tông màu với các badge subtle khác trong hệ thống
     * (ví dụ badge "Loại" ở bảng stock-movement).
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::Draft     => 'badge bg-secondary-subtle text-secondary border border-secondary-subtle',
            self::Approved  => 'badge bg-warning-subtle text-warning border border-warning-subtle',
            self::Completed => 'badge bg-success-subtle text-success border border-success-subtle',
            self::Cancelled => 'badge bg-danger-subtle text-danger border border-danger-subtle',
        };
    }

    /**
     * Danh sách [value => label] theo context, dùng đổ vào <select>.
     * Bổ sung so với HasOptions::options() (vốn không nhận tham số,
     * mặc định context 'movement').
     *
     * @param  string  $context  'movement' | 'inventory_check'
     */
    public static function optionsFor(string $context = 'movement'): array
    {
        return array_column(
            array_map(
                fn ($case) => ['value' => $case->value, 'label' => $case->label($context)],
                self::cases()
            ),
            'label',
            'value'
        );
    }
}