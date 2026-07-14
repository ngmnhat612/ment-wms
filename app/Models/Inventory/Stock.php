<?php

namespace App\Models\Inventory;

use App\Enums\LotSerialStatus;
use App\Enums\StockRotation;
use Illuminate\Database\Eloquent\Model;
use App\Models\Master\Product;
use App\Models\Master\Location;
use App\Models\Master\Warehouse;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'previous_location_id',
        'current_location_id',
        'lot_id',
        'serial_id',
        'quantity',
        'reserved_qty',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity'     => 'decimal:3',
            'reserved_qty' => 'decimal:3',
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
            'status'       => LotSerialStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function currentLocation()
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function previousLocation()
    {
        return $this->belongsTo(Location::class, 'previous_location_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function serial()
    {
        return $this->belongsTo(Serial::class, 'serial_id');
    }

    // ===== SCOPES =====

    public function scopeAvailable($query)
    {
        return $query->where('available_qty', '>', 0);
    }

    public function scopeAtLocation($query, int $locationId)
    {
        return $query->where('current_location_id', $locationId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeFefo($query)
    {
        return $query->join('lots', 'stocks.lot_id', '=', 'lots.id')
                     ->orderByRaw('CASE WHEN lots.expiry_date IS NULL THEN 1 ELSE 0 END')
                     ->orderBy('lots.expiry_date')
                     ->orderBy('lots.received_date');
    }

    public function scopeFifo($query)
    {
        return $query->join('lots', 'stocks.lot_id', '=', 'lots.id')
                     ->orderBy('lots.received_date')
                     ->orderBy('stocks.id');
    }

    // ===== HELPERS =====

    public function getAvailableQtyAttribute(): float
    {
        if (array_key_exists('available_qty', $this->attributes)) {
            return (float) $this->attributes['available_qty'];
        }
        return (float) $this->quantity - (float) $this->reserved_qty;
    }

    public function isLotOnly(): bool
    {
        return $this->lot_id !== null && $this->serial_id === null;
    }

    public function isLotAndSerial(): bool
    {
        return $this->lot_id !== null && $this->serial_id !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '—';
    }
}