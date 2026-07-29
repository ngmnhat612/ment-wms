<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\DB;

trait ChecksForeignKeyUsage
{
    /**
     * Map tên bảng (SQL) -> tên hiển thị tiếng Việt cho thông báo lỗi.
     * Bảng nào không có trong map sẽ hiển thị nguyên tên bảng (fallback).
     */
    protected function referencingTableLabels(): array
    {
        return [
            'products'                  => 'Vật tư',
            'stock_receipt_detail'      => 'Chi tiết phiếu nhập kho',
            'stock_issue_detail'        => 'Chi tiết phiếu xuất kho',
            'stock_in_request_detail'   => 'Chi tiết yêu cầu nhập kho',
            'stock_out_request_detail'  => 'Chi tiết yêu cầu xuất kho',
            'inventory_check_detail'    => 'Chi tiết kiểm kê',
            'stock_adjustment_details'  => 'Chi tiết điều chỉnh tồn kho',
            'putaway_rules'             => 'Quy tắc gán vị trí',
            'reorder_rules'             => 'Quy tắc tái đặt hàng',
            'warehouses'                => 'Kho',
            'locations'                 => 'Vị trí',
            'employees'                 => 'Nhân viên',
            'accounts'                  => 'Tài khoản',
            'categories'                => 'Danh mục',
            'uom_conversions'           => 'Quy đổi đơn vị tính',
            'stock_receipt'             => 'Phiếu nhập kho',
            'stock_issue'               => 'Phiếu xuất kho',
            'stocks'                    => 'Tồn kho',
            // Thêm bảng mới vào đây khi cần đổi tên hiển thị,
            // KHÔNG BẮT BUỘC — nếu quên, hệ thống vẫn tự phát hiện
            // và báo bằng tên bảng SQL (fallback), không bỏ sót.
        ];
    }

    /**
     * Tìm mọi bảng đang tham chiếu tới $table.$column = $id
     * bằng cách đọc trực tiếp foreign key constraints từ SQL Server.
     * Tự động bắt được bảng mới thêm vào sau này, không cần sửa code.
     *
     * Không cache: với quy mô ~20-30 bảng, truy vấn sys.foreign_keys chỉ
     * mất khoảng 1-2ms, không đáng để đánh đổi lấy nguy cơ cache cũ bỏ sót
     * bảng mới thêm sau migration (xem thảo luận đã chốt — không dùng cache
     * ở quy mô này).
     *
     * @param  array<string>  $ignoreTables  Danh sách bảng SQL bỏ qua khi kiểm tra
     *         (dùng cho các bảng phụ thuộc sẽ được tự động xóa/cascade cùng bản ghi
     *         cha, nên không được tính là "đang sử dụng" để chặn xóa).
     * @return array<string> Danh sách tên hiển thị các nơi đang sử dụng (rỗng nếu không bị dùng ở đâu)
     */
    protected function findUsages(string $table, string $column, int $id, array $ignoreTables = []): array
    {
        $foreignKeys = DB::select("
            SELECT 
                OBJECT_NAME(fk.parent_object_id) AS referencing_table,
                COL_NAME(fc.parent_object_id, fc.parent_column_id) AS referencing_column
            FROM sys.foreign_keys fk
            INNER JOIN sys.foreign_key_columns fc 
                ON fk.object_id = fc.constraint_object_id
            WHERE OBJECT_NAME(fk.referenced_object_id) = ?
              AND COL_NAME(fc.referenced_object_id, fc.referenced_column_id) = ?
        ", [$table, $column]);

        $labels = $this->referencingTableLabels();
        $usages = [];

        foreach ($foreignKeys as $fk) {
            if (in_array($fk->referencing_table, $ignoreTables, true)) {
                continue;
            }

            $exists = DB::table($fk->referencing_table)
                ->where($fk->referencing_column, $id)
                ->exists();

            if ($exists) {
                $usages[] = $labels[$fk->referencing_table] ?? $fk->referencing_table;
            }
        }

        return array_unique($usages);
    }

    /**
     * Ném RuntimeException với thông báo chuẩn nếu bản ghi còn bị tham chiếu.
     * Dùng chung cho mọi Service Master data thay vì viết lại điều kiện ở từng nơi.
     *
     * Định dạng thông báo cố định:
     * Không thể xóa <Nhãn> "<Tên>" vì đang được sử dụng bởi: <A, B>.
     * Vui lòng chuyển sang trạng thái Ngưng hoạt động thay vì xóa.
     *
     * @param  array<string>  $ignoreTables  Bảng phụ thuộc sẽ tự xóa cùng bản ghi cha,
     *         không tính là ràng buộc chặn xóa (ví dụ: reorder_rules, putaway_rules
     *         khi xóa product — 2 bảng này được Service tự xóa trước, không phải
     *         lý do hợp lệ để chặn).
     */
    protected function guardNotInUse(string $table, string $column, int $id, string $label, string $name, array $ignoreTables = []): void
    {
        $usages = $this->findUsages($table, $column, $id, $ignoreTables);

        if (!empty($usages)) {
            $places = implode(', ', $usages);
            throw new \RuntimeException(
                "Không thể xóa {$label} \"{$name}\" vì đang được sử dụng bởi: {$places}. " .
                "Vui lòng chuyển sang trạng thái Ngưng hoạt động thay vì xóa."
            );
        }
    }
}