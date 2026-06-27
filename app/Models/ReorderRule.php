<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReorderRule extends Model
{
    protected $table = 'reorder_rules';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'employee_id',
        'min_qty',
        'max_qty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'min_qty' => 'decimal:3',
            'max_qty' => 'decimal:3',
            'status'  => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Người phụ trách theo dõi ngưỡng tồn này.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }

    /**
     * Scope: các rules đang dưới ngưỡng min_qty.
     * Join với bảng stocks (tổng available_qty theo warehouse).
     */
    public function scopeBelowMin($query)
    {
        return $query
            ->active()
            ->with(['product', 'warehouse', 'employee'])
            ->selectRaw('reorder_rules.*, COALESCE(s.total_qty, 0) AS current_stock')
            ->leftJoinSub(
                DB::table('stocks')
                    ->selectRaw('product_id, warehouse_id, SUM(available_qty) AS total_qty')
                    ->groupBy('product_id', 'warehouse_id'),
                's',
                function ($join) {
                    $join->on('s.product_id',   '=', 'reorder_rules.product_id')
                         ->on('s.warehouse_id', '=', 'reorder_rules.warehouse_id');
                }
            )
            ->whereRaw('COALESCE(s.total_qty, 0) < reorder_rules.min_qty');
    }

    // ===== HELPERS =====

    /**
     * Số lượng cần đặt thêm để đạt max_qty từ tồn hiện tại.
     */
    public function getQtyToOrderAttribute(): float
    {
        $currentQty = DB::table('stocks')
            ->where('product_id',   $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->sum('available_qty');

        $needed = (float) $this->max_qty - (float) $currentQty;

        return $needed > 0 ? $needed : 0.0;
    }

    public function isBelowMin(): bool
    {
        $currentQty = DB::table('stocks')
            ->where('product_id',   $this->product_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->sum('available_qty');

        return (float) $currentQty < (float) $this->min_qty;
    }
}
