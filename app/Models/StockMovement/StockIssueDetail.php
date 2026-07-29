<?php

namespace App\Models\StockMovement;

use App\Models\Inventory\Lot;
use App\Models\Inventory\Serial;
use App\Models\Master\Employee;
use App\Models\Master\Location;
use Illuminate\Database\Eloquent\Model;

class StockIssueDetail extends Model
{
    protected $table = 'stock_issue_detail';

    protected $fillable = [
        'stock_issue_line_id', 'lot_id', 'serial_id', 'location_id',
        'lot_number_snapshot', 'serial_number_snapshot',
        'actual_qty', 'sub_warehouse', 'receiver_id', 'note',
    ];

    protected $casts = [
        'stock_issue_line_id' => 'integer',
        'lot_id'              => 'integer',
        'serial_id'           => 'integer',
        'location_id'         => 'integer',
        'receiver_id'         => 'integer',
        'actual_qty'          => 'decimal:3',
    ];

    // ===== RELATIONSHIPS =====

    public function line()
    {
        return $this->belongsTo(StockIssueLine::class, 'stock_issue_line_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function serial()
    {
        return $this->belongsTo(Serial::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }
}