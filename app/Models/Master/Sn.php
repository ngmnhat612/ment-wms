<?php

namespace App\Models\Master;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;   // UPDATE

class Sn extends Model
{
    use SoftDeletes, HasFactory; // UPDATE

    protected $table = 'sns';

    protected $fillable = [
        'code',
        'name',
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

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }
}
