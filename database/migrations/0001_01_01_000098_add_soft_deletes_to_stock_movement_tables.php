<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm deleted_at (soft delete) cho toàn bộ bảng thuộc luồng Nhập/Xuất kho.
 * Trước migration này, StockReceipt/StockIssue (và các bảng line/detail con)
 * KHÔNG có deleted_at -> $model->delete() là XÓA CỨNG thật sự (Eloquent
 * fallback về hard delete khi model không dùng SoftDeletes).
 *
 * Đặt SAU migration tạo bảng gốc (000024-000032) và TRƯỚC migration
 * add_soft_deletes_to_master_tables (000099) — không bắt buộc về thứ tự thực
 * thi (2 migration không phụ thuộc nhau), chỉ để nhóm rõ theo ngữ cảnh khi
 * đọc danh sách migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'stock_receipt',
            'stock_receipt_line',
            'stock_receipt_detail',
            'stock_issue',
            'stock_issue_line',
            'stock_issue_detail',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'stock_receipt',
            'stock_receipt_line',
            'stock_receipt_detail',
            'stock_issue',
            'stock_issue_line',
            'stock_issue_detail',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};