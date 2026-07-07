<?php

namespace App\Models\StockRequest;

use App\Models\Master\Employee;
use Illuminate\Database\Eloquent\Model;

class StockInRequestDetail extends Model
{
    protected $table = 'stock_in_request_detail';

    protected $fillable = [
        'stock_in_request_id', 'reference_no', 'received_date',
        'product_code', 'product_name', 'product_specification', 'brand_name',
        'quantity', 'uom_name', 'lot_number', 'note',
        'new_product_code', 'qc_date', 'qc_employee_id', 'qc_result',
        'rejected_qty', 'solution', 'requester_id',
    ];

    protected $casts = [
        'received_date'       => 'date',
        'qc_date'             => 'date',
        'quantity'            => 'decimal:3',
        'rejected_qty'        => 'decimal:3',
        'stock_in_request_id' => 'integer',
        'lot_number'          => 'integer',
        'qc_employee_id'      => 'integer',
        'requester_id'        => 'integer',
    ];

    public function stockInRequest()
    {
        return $this->belongsTo(StockInRequest::class, 'stock_in_request_id');
    }

    public function qcEmployee()
    {
        return $this->belongsTo(Employee::class, 'qc_employee_id');
    }

    public function requester()
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }
}