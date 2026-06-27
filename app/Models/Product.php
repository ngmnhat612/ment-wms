<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\StockRotation;
use App\Enums\TrackingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    
    protected $table = 'products';

    protected $fillable = [
        'code',
        'name',
        'category_id',
        'uom_id',
        'parent_id',
        'specification',
        'alert_before_expiry',
        'stock_rotation',
        'image_path',
        'status',
        'tracking_type',
    ];

    protected function casts(): array
    {
        return [
            'tracking_type'       => TrackingType::class,
            'stock_rotation'      => StockRotation::class,
            'alert_before_expiry' => 'integer',
            'status'              => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function lots()
    {
        return $this->hasMany(Lot::class, 'product_id');
    }

    public function serials()
    {
        return $this->hasManyThrough(
            Serial::class,
            Lot::class,
            'product_id', // FK trên lots
            'lot_id',     // FK trên serials
        );
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'product_id');
    }

    public function reorderRules()
    {
        return $this->hasMany(ReorderRule::class, 'product_id');
    }

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(Product::class, 'parent_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }

    // ===== HELPERS =====

    public function isLotTracked(): bool
    {
        return $this->tracking_type === TrackingType::Lot;
    }

    public function isSerialTracked(): bool
    {
        return $this->tracking_type === TrackingType::LotAndSerial;
    }

    public function getTrackingLabelAttribute(): string
    {
        return $this->tracking_type?->label() ?? '—';
    }

    public function getRotationLabelAttribute(): string
    {
        return $this->stock_rotation?->label() ?? '—';
    }

}
