<?php

namespace App\Models\StockMovement;

use App\Enums\DocumentStatus;
use App\Models\Master\Account;
use App\Models\Master\Warehouse;
use App\Models\StockRequest\StockOutRequest;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockIssue extends Model
{
    use LogsActivity;

    protected $table = 'stock_issue';

    protected $fillable = [
        'warehouse_id', 'stock_out_request_id', 'code',
        'created_by', 'approved_by', 'status', 'note', 'issue_date',
    ];

    protected $casts = [
        'issue_date'           => 'date',
        'status'               => DocumentStatus::class,
        'warehouse_id'         => 'integer',
        'stock_out_request_id' => 'integer',
        'created_by'           => 'integer',
        'approved_by'          => 'integer',
    ];

    // ===== RELATIONSHIPS =====

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockOutRequest()
    {
        return $this->belongsTo(StockOutRequest::class, 'stock_out_request_id');
    }

    public function lines()
    {
        return $this->hasMany(StockIssueLine::class, 'stock_issue_id');
    }

    public function details()
    {
        return $this->hasManyThrough(
            StockIssueDetail::class,
            StockIssueLine::class,
            'stock_issue_id',      // FK trên stock_issue_line trỏ về stock_issue
            'stock_issue_line_id', // FK trên stock_issue_detail trỏ về stock_issue_line
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
            ->logOnly(['code', 'status', 'warehouse_id', 'stock_out_request_id', 'issue_date', 'note'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Tạo phiếu xuất kho \"{$this->code}\"",
                'updated' => "Cập nhật phiếu xuất kho \"{$this->code}\"",
                'deleted' => "Xóa phiếu xuất kho \"{$this->code}\"",
                default   => $event,
            });
    }
}