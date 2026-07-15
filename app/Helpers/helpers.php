<?php

if (! function_exists('truncate_text')) {
    /**
     * Rút gọn chuỗi văn bản, thêm dấu "..." nếu vượt quá độ dài cho phép.
     * Dùng chung cho các cột "Ghi chú", "Thông số kỹ thuật" ở index.blade các module master data.
     *
     * @param  string|null  $value
     * @param  int  $limit
     * @return string
     */
    function truncate_text(?string $value, int $limit = 100): string
    {
        return \Illuminate\Support\Str::limit($value ?? '-', $limit);
    }
}
