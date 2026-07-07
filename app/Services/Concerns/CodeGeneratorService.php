<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\DB;

class CodeGeneratorService
{
    /**
     * Sinh mã theo quy tắc: <prefix> + <số thứ tự N chữ số>
     *
     * Ví dụ: generateCode('products', 'code', 'DD', 4) → 'DD0003'
     *
     * @param string $table   Tên bảng cần kiểm tra
     * @param string $column  Tên cột chứa mã
     * @param string $prefix  Tiền tố (ví dụ: 'DD', 'NCC', 'PN')
     * @param int    $digits  Số chữ số của phần thứ tự (mặc định 4)
     *
     * @throws \RuntimeException nếu vượt quá giới hạn
     */
    public function generateCode(
        string $table,
        string $column,
        string $prefix,
        int    $digits = 4,
    ): string {
        $maxAllowed = (int) str_repeat('9', $digits); // 9999 nếu digits=4

        // Lấy tất cả mã có prefix, tính max sequence trong PHP
        // tránh dùng hàm SQL Server-specific như SUBSTRING/CAST
        $maxSeq = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->pluck($column)
            ->map(fn($code) => $this->extractSequence($code, $prefix, $digits))
            ->filter(fn($n) => $n !== null)
            ->max();

        $next = ($maxSeq ?? 0) + 1;

        if ($next > $maxAllowed) {
            throw new \RuntimeException(
                "Prefix \"{$prefix}\" đã đạt giới hạn {$maxAllowed} mã."
            );
        }

        return $prefix . str_pad($next, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Tách phần số thứ tự, trả về null nếu không đúng định dạng.
     */
    private function extractSequence(string $code, string $prefix, int $digits): ?int
    {
        $expectedLen = strlen($prefix) + $digits;

        if (strlen($code) !== $expectedLen) {
            return null;
        }

        $suffix = substr($code, strlen($prefix));

        if (!ctype_digit($suffix)) {
            return null;
        }

        return (int) $suffix;
    }

    /**
     * Sinh mã biến thể: <parent_code>.<số thứ tự>
     * Ví dụ: DD1234 → DD1234.1 → DD1234.2
     */
    public function generateVariantCode(string $parentCode): string
    {
        $maxSuffix = DB::table('products')
            ->where('code', 'like', $parentCode . '.%')
            ->pluck('code')
            ->map(function ($code) use ($parentCode) {
                $suffix = substr($code, strlen($parentCode) + 1); // bỏ "DD1234."
                return ctype_digit($suffix) ? (int) $suffix : null;
            })
            ->filter(fn($n) => $n !== null)
            ->max();

        $next = ($maxSuffix ?? 0) + 1;

        return $parentCode . '.' . $next;
    }

    /**
     * Sinh mã Lô tự động theo quy tắc: "LO" + <số thứ tự tiếp theo>, không zero-pad.
     *
     * Từ khi bảng lots có cột lot_number kiểu INT riêng (tách khỏi lot_code text),
     * chỉ cần lấy MAX(lot_number) rồi +1 — không cần parse chuỗi như trước.
     * Số thứ tự dùng chung toàn hệ thống, không phụ thuộc sản phẩm.
     *
     * Ví dụ: đang có lot_number=9 (lot_code="LO9") → sinh ra lot_code="LO10", lot_number=10.
     * Nếu chưa có lô nào, bắt đầu từ LO1 (lot_number=1).
     *
     * "Tên lô" hiển thị chính là cột lot_number này (ví dụ lot_code LO12 → tên "12").
     *
     * @return array{code: string, number: int} vd ['code' => 'LO10', 'number' => 10]
     */
    public function generateLotCode(string $prefix = 'LO'): array
    {
        $maxNumber = (int) (DB::table('lots')->max('lot_number') ?? 0);
        $nextNumber = $maxNumber + 1;

        return [
            'code'   => $prefix . $nextNumber,
            'number' => $nextNumber,
        ];
    }
}