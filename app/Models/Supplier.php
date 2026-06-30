<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'code',
        'name',
        'tax_code',
        'phone',
        'email',
        'address',
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

    public function lots()
    {
        return $this->hasMany(Lot::class, 'supplier_id');
    }

    // public function stockReceiptDetails()
    // {
    //     return $this->hasMany(StockReceiptDetail::class, 'supplier_id');
    // }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }
}
