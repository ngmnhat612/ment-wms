<?php

namespace App\Models\Stocktake;

use App\Models\Inventory\Lot;
use App\Models\Inventory\Serial;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Master\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockAdjustmentDetail extends Model
{
    use SoftDeletes;

    protected $table = 'stock_adjustment_details';

    protected $fillable = [
        'stock_adjustment_id', 'check_detail_id', 'product_id',
        'system_location_id', 'actual_location_id', 'lot_id', 'serial_id',
        'uom_id', 'system_qty', 'actual_qty', 'note',
    ];

    protected function casts(): array
    {
        return [
            'stock_adjustment_id' => 'integer',
            'check_detail_id'     => 'integer',
            'product_id'          => 'integer',
            'system_location_id'  => 'integer',
            'actual_location_id'  => 'integer',
            'lot_id'              => 'integer',
            'serial_id'           => 'integer',
            'uom_id'              => 'integer',
            'system_qty'          => 'decimal:3',
            'actual_qty'          => 'decimal:3',
            'diff_qty'            => 'decimal:3',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function checkDetail()
    {
        return $this->belongsTo(InventoryCheckDetail::class, 'check_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function systemLocation()
    {
        return $this->belongsTo(Location::class, 'system_location_id');
    }

    public function actualLocation()
    {
        return $this->belongsTo(Location::class, 'actual_location_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function serial()
    {
        return $this->belongsTo(Serial::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }
}