<?php

namespace App\Models\StockRequest;

use App\Models\Master\Employee;
use Illuminate\Database\Eloquent\Model;

class StockOutRequestDetail extends Model
{
    protected $table = 'stock_out_request_detail';

    protected $fillable = [
        'stock_out_request_id', 'issue_date', 'product_code', 'product_name',
        'quantity', 'uom_name', 'actual_qty', 'receiver_id', 'sn_code',
        'lot_number', 'note', 'serial_number',
    ];

    protected $casts = [
        'issue_date'            => 'date',
        'quantity'              => 'decimal:3',
        'actual_qty'            => 'decimal:3',
        'stock_out_request_id'  => 'integer',
        'lot_number'            => 'integer',
        'receiver_id'           => 'integer',
    ];

    public function stockOutRequest()
    {
        return $this->belongsTo(StockOutRequest::class, 'stock_out_request_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }
}