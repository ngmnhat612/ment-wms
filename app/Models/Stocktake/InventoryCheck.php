<?php

namespace App\Models\Stocktake;

use App\Enums\InventoryCheckScope;
use App\Enums\InventoryCheckType;
use App\Enums\InventoryCheckStatus;
use App\Models\Master\Account;
use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InventoryCheck extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'inventory_check';

    protected $fillable = [
        'warehouse_id', 'code', 'check_scope', 'check_type', 'created_by',
        'status', 'check_date', 'purpose', 'note', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_id' => 'integer',
            'created_by'   => 'integer',
            'check_scope'  => InventoryCheckScope::class,
            'check_type'   => InventoryCheckType::class,
            'status'       => InventoryCheckStatus::class,
            'check_date'   => 'date',
            'completed_at' => 'datetime',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(InventoryCheckDetail::class, 'inventory_check_id');
    }

    public function freezes()
    {
        return $this->hasMany(InventoryFreeze::class, 'check_id');
    }

    public function activeFreeze()
    {
        return $this->hasOne(InventoryFreeze::class, 'check_id')
            ->whereNull('unfrozen_at')
            ->latestOfMany();
    }

    public function adjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'check_id');
    }

    // ===== SCOPES =====

    public function scopeDraft($query)
    {
        return $query->where('status', InventoryCheckStatus::Draft);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', InventoryCheckStatus::InProgress);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', InventoryCheckStatus::Completed);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', InventoryCheckStatus::Cancelled);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeEntireWarehouse($query)
    {
        return $query->where('check_scope', InventoryCheckScope::EntireWarehouse);
    }

    public function scopeByArea($query)
    {
        return $query->where('check_scope', InventoryCheckScope::ByArea);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'status', 'warehouse_id', 'check_scope', 'check_type', 'check_date', 'purpose', 'note'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Tạo phiếu kiểm kê \"{$this->code}\"",
                'updated' => "Cập nhật phiếu kiểm kê \"{$this->code}\"",
                'deleted' => "Xóa phiếu kiểm kê \"{$this->code}\"",
                default   => $event,
            });
    }
}