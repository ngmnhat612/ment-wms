<?php

namespace App\Services;

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
}