<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'code',
        'name',
        'unique_name',
        'phone_number',
        'department_id',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    /**
     * Bộ phận của nhân viên.
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Tài khoản đăng nhập của nhân viên (1-1).
     */
    public function account()
    {
        return $this->hasOne(Account::class, 'employee_id');
    }

    /**
     * Các kho được phân công (N-N qua warehouse_employees).
     */
    public function warehouses()
    {
        return $this->belongsToMany(
                Warehouse::class,
                'warehouse_employees',
                'employee_id',
                'warehouse_id'
            )
            ->using(WarehouseEmployee::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Kho chính (is_primary = true) — dùng làm mặc định sau khi login.
     */
    public function primaryWarehouse()
    {
        return $this->warehouses()->wherePivot('is_primary', true)->first();
    }

    public function managedWarehouses()
    {
        return $this->hasMany(Warehouse::class, 'manager_id');
    }

    public function reorderRules()
    {
        return $this->hasMany(ReorderRule::class, 'employee_id');
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

    public function isInDepartment(string $departmentCode): bool
    {
        return $this->department?->code === $departmentCode;
    }
}