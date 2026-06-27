<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WarehouseEmployee extends Pivot
{
    protected $table = 'warehouse_employees';

    public $incrementing = true; // bảng có PK id riêng

    protected $fillable = [
        'warehouse_id',
        'employee_id',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
