<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $table = 'warehouses';

    protected $fillable = [
        'parent_id',
        'manager_id',
        'root_location_id',
        'code',
        'name',
        'phone',
        'address',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function parent()
    {
        return $this->belongsTo(Warehouse::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Warehouse::class, 'parent_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /**
     * Vị trí gốc của kho (root node trong cây location).
     */
    public function rootLocation()
    {
        return $this->belongsTo(Location::class, 'root_location_id');
    }

    /**
     * Tất cả vị trí nội bộ thuộc kho này.
     */
    public function locations()
    {
        return $this->hasMany(Location::class, 'warehouse_id');
    }

    /**
     * Các nhân viên được phân công vào kho (qua bảng pivot warehouse_employees).
     */
    public function employees()
    {
        return $this->belongsToMany(
                Employee::class,
                'warehouse_employees',
                'warehouse_id',
                'employee_id'
            )
            ->using(WarehouseEmployee::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function reorderRules()
    {
        return $this->hasMany(ReorderRule::class, 'warehouse_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }

    // ===== HELPERS =====

    public function isActive(): bool
    {
        return $this->status === ActiveStatus::Active;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }
}
