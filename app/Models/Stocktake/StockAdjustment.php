<?php

namespace App\Models\Stocktake;

use App\Enums\DocumentStatus;
use App\Models\Master\Account;
use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockAdjustment extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'stock_adjustment';

    protected $fillable = [
        'warehouse_id', 'code', 'check_id', 'approved_by', 'created_by',
        'status', 'adjustment_date', 'note',
    ];

    protected function casts(): array
    {
        return [
            'warehouse_id'    => 'integer',
            'check_id'        => 'integer',
            'approved_by'     => 'integer',
            'created_by'      => 'integer',
            'status'          => DocumentStatus::class,
            'adjustment_date' => 'date',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryCheck()
    {
        return $this->belongsTo(InventoryCheck::class, 'check_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Account::class, 'approved_by');
    }

    public function details()
    {
        return $this->hasMany(StockAdjustmentDetail::class, 'stock_adjustment_id');
    }

    // ===== SCOPES =====

    public function scopeDraft($query)
    {
        return $query->where('status', DocumentStatus::Draft);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', DocumentStatus::Completed);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', DocumentStatus::Cancelled);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'status', 'warehouse_id', 'check_id', 'adjustment_date', 'note'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Tạo phiếu điều chỉnh \"{$this->code}\"",
                'updated' => "Cập nhật phiếu điều chỉnh \"{$this->code}\"",
                'deleted' => "Xóa phiếu điều chỉnh \"{$this->code}\"",
                default   => $event,
            });
    }
}