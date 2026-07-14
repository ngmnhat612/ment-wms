<?php

namespace App\Models\Master;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;   // UPDATE
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Account extends Authenticatable
{
    use Notifiable, HasRoles;
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'employee_id',
        'username',
        'password',
        'status',
        'is_protected',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status'   => ActiveStatus::class,
            'is_protected' => 'boolean',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
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

    /**
     * Shortcut lấy department thông qua employee.
     * Dùng trong Policy: $account->department?->code
     */
    public function getDepartmentAttribute(): ?Department
    {
        return $this->employee?->department;
    }

    /**
     * Kiểm tra account thuộc bộ phận theo code.
     * Dùng trong Policy thay vì truy cập chuỗi quan hệ dài.
     */
    // public function isInDepartment(string $departmentCode): bool
    // {
    //     return $this->department?->code === $departmentCode;
    // }

    public function isInDepartmentNamed(string $departmentName): bool
    {
        return $this->employee?->department?->name === $departmentName;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->employee?->unique_name ?? $this->username;
    }
}
