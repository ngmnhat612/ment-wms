<?php

namespace App\Models\Master;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;  

class Uom extends Model
{
    use HasFactory;

    protected $table = 'uoms';

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

    public function products()
    {
        return $this->hasMany(Product::class, 'uom_id');
    }

    public function conversionsFrom()
    {
        return $this->hasMany(UomConversion::class, 'from_uom_id');
    }

    public function conversionsTo()
    {
        return $this->hasMany(UomConversion::class, 'to_uom_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }
}
