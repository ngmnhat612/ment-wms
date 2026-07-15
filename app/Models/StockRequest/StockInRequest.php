<?php

namespace App\Models\StockRequest;

use App\Enums\DocumentStatus;
use App\Models\Master\Account;
use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Model;

class StockInRequest extends Model
{
    protected $table = 'stock_in_request';

    protected $fillable = [
        'warehouse_id', 'created_by', 'code', 'request_date', 'status', 'note',
    ];

    protected $casts = [
        'status'       => DocumentStatus::class,
        'warehouse_id' => 'integer',
        'created_by'   => 'integer',
        'request_date' => 'date',
    ];

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
        return $this->hasMany(StockInRequestDetail::class, 'stock_in_request_id');
    }
}