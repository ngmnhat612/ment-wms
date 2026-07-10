<?php

namespace App\Models\Stocktake;

use App\Models\Inventory\Lot;
use App\Models\Inventory\Serial;
use App\Models\Master\Employee;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryCheckDetail extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_check_detail';

    protected $fillable = [
        'inventory_check_id', 'product_id', 'lot_id', 'serial_id',
        'system_location_id', 'actual_location_id', 'uom_id',
        'system_qty', 'actual_qty', 'assignment_id', 'note',
    ];

    protected function casts(): array
    {
        return [
            'inventory_check_id' => 'integer',
            'product_id'         => 'integer',
            'lot_id'             => 'integer',
            'serial_id'          => 'integer',
            'system_location_id' => 'integer',
            'actual_location_id' => 'integer',
            'uom_id'             => 'integer',
            'assignment_id'      => 'integer',
            'system_qty'         => 'decimal:3',
            'actual_qty'         => 'decimal:3',
            'diff_qty'           => 'decimal:3',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function inventoryCheck()
    {
        return $this->belongsTo(InventoryCheck::class, 'inventory_check_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function serial()
    {
        return $this->belongsTo(Serial::class);
    }

    public function systemLocation()
    {
        return $this->belongsTo(Location::class, 'system_location_id');
    }

    public function actualLocation()
    {
        return $this->belongsTo(Location::class, 'actual_location_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Employee::class, 'assignment_id');
    }

    public function adjustmentDetail()
    {
        return $this->hasOne(StockAdjustmentDetail::class, 'check_detail_id');
    }

    // ===== HELPERS =====

    public function hasDifference(): bool
    {
        return (float) $this->diff_qty !== 0.0;
    }
}