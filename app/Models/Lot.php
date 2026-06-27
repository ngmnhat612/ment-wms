<?php

namespace App\Models;

use App\Enums\LotSerialStatus;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $table = 'lots';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'lot_number',
        'received_date',
        'manufacture_date',
        'expiry_date',
        'status',
        'scan_code',
    ];

    protected function casts(): array
    {
        return [
            'received_date'    => 'date',
            'manufacture_date' => 'date',
            'expiry_date'      => 'date',
            'status'           => LotSerialStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function serials()
    {
        return $this->hasMany(Serial::class, 'lot_id');
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'lot_id');
    }

    // ===== SCOPES =====

    public function scopeInStock($query)
    {
        return $query->where('status', LotSerialStatus::InStock);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                     ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    // ===== HELPERS =====

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '—';
    }
}