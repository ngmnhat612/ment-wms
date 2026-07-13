<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CHỈ 2 bảng còn dùng soft delete trong nhóm Master data:
 * reorder_rules, putaway_rules.
 *
 * Lý do giữ lại (khác các bảng Master data còn lại đã bỏ soft delete):
 * cần deleted_at để hỗ trợ restore-from-trashed, tránh vi phạm
 * unique(product_id, warehouse_id) khi người dùng xóa rồi tạo lại đúng
 * tổ hợp product+warehouse. Xem ReorderRuleService::create(),
 * PutawayRuleService::create().
 *
 * 12 bảng Master data còn lại (products, categories, uoms, uom_conversions,
 * suppliers, employees, accounts, warehouses, locations, brands,
 * departments, sns) đã được rút khỏi danh sách này — status (ActiveStatus)
 * đã đủ để biểu diễn "ngưng dùng", và Service layer có kiểm tra FK trước
 * khi xóa cứng (xem App\Services\Concerns\ChecksForeignKeyUsage) nên
 * deleted_at ở các bảng đó là dư thừa.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'reorder_rules',
            'putaway_rules',
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
            'reorder_rules',
            'putaway_rules',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};