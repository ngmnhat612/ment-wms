<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;

class PutawayRule extends Model
{
    protected $table = 'putaway_rules';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'category_id',
        'location_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function destinationLocation()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // ===== HELPERS =====

    public function isProductRule(): bool
    {
        return $this->product_id !== null;
    }

    public function isCategoryRule(): bool
    {
        return $this->category_id !== null;
    }
}