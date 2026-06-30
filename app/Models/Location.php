<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    protected $table = 'locations';

    protected $fillable = [
        'parent_id',
        'warehouse_id',
        'code',
        'name',
        'type',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'type'   => LocationType::class,
            'status' => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function parent()
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    /**
     * Eager-load đệ quy toàn bộ cây con — dùng cho view tree.
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Kho nào đang dùng location này làm vị trí gốc.
     */
    public function warehouseAsRoot()
    {
        return $this->hasOne(Warehouse::class, 'root_location_id');
    }

    /**
     * Tồn kho hiện tại tại vị trí này.
     */
    public function stocks()
    {
        return $this->hasMany(Stock::class, 'current_location_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }

    public function scopeInternal($query)
    {
        return $query->where('type', LocationType::Internal->value);
    }

    public function scopeVirtual($query)
    {
        return $query->where('type', LocationType::Virtual->value);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // ===== HELPERS =====

    /**
     * Đường dẫn đầy đủ: Kho › Khu vực › Vị trí.
     */
    public function getFullPathAttribute(): string
    {
        return $this->parent
            ? $this->parent->full_path . ' › ' . $this->name
            : $this->name;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '—';
    }

    public function isVirtual(): bool
    {
        return $this->type === LocationType::Virtual;
    }

    public function isInternal(): bool
    {
        return $this->type === LocationType::Internal;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function hasStock(): bool
    {
        return $this->stocks()->where('quantity', '>', 0)->exists();
    }

    public function isRootLocation(): bool
    {
        return $this->warehouseAsRoot()->exists();
    }

    /**
     * Lấy toàn bộ ID con cháu (đệ quy) — dùng cho query tồn kho theo khu vực.
     */
    public function getDescendantIds(): array
    {
        $ids      = [];
        $children = $this->children()->select('id')->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $childFull = static::with('children')->find($child->id);
            if ($childFull) {
                $ids = array_merge($ids, $childFull->getDescendantIds());
            }
        }

        return $ids;
    }
}
