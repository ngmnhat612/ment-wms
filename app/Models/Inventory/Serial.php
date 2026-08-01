<?php

namespace App\Models\Inventory;

use App\Enums\LotSerialStatus;
use Illuminate\Database\Eloquent\Model;

class Serial extends Model
{
    protected $table = 'serials';

    protected $fillable = [
        'product_id',
        'lot_id',
        'serial_number',
        'status',
        'scan_code',
    ];

    protected function casts(): array
    {
        return [
            'status' => LotSerialStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(\App\Models\Master\Product::class, 'product_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'serial_id');
    }

    // ===== SCOPES =====

    public function scopeInStock($query)
    {
        return $query->where('status', LotSerialStatus::InStock);
    }

    // ===== HELPERS =====

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '—';
    }
}