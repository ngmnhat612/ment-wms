<?php

namespace App\Models\Master;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';

    protected $fillable = [
        'code',
        'name',
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

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
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
}
