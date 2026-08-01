<?php

namespace App\Models\StockMovement;

use App\Enums\DocumentStatus;
use App\Models\Master\Account;
use App\Models\Master\Warehouse;
use App\Models\StockRequest\StockInRequest;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockReceipt extends Model
{
    use LogsActivity;

    protected $table = 'stock_receipt';

    protected $fillable = [
        'warehouse_id', 'stock_in_request_id', 'code',
        'created_by', 'approved_by', 'status', 'note', 'receipt_date',
    ];

    protected $casts = [
        'receipt_date'        => 'date',
        'status'              => DocumentStatus::class,
        'warehouse_id'        => 'integer',
        'stock_in_request_id' => 'integer',
        'created_by'          => 'integer',
        'approved_by'         => 'integer',
    ];

    // ===== RELATIONSHIPS =====

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockInRequest()
    {
        return $this->belongsTo(StockInRequest::class, 'stock_in_request_id');
    }

    public function lines()
    {
        return $this->hasMany(StockReceiptLine::class, 'stock_receipt_id');
    }

    public function details()
    {
        return $this->hasManyThrough(
            StockReceiptDetail::class,
            StockReceiptLine::class,
            'stock_receipt_id',      // FK trên stock_receipt_line trỏ về stock_receipt
            'stock_receipt_line_id', // FK trên stock_receipt_detail trỏ về stock_receipt_line
            'id',
            'id'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(Account::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Account::class, 'approved_by');
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
            ->logOnly(['code', 'status', 'warehouse_id', 'stock_in_request_id', 'receipt_date', 'note'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Tạo phiếu nhập kho \"{$this->code}\"",
                'updated' => "Cập nhật phiếu nhập kho \"{$this->code}\"",
                'deleted' => "Xóa phiếu nhập kho \"{$this->code}\"",
                default   => $event,
            });
    }
}