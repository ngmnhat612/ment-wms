<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;

class UomConversion extends Model
{
    protected $table = 'uom_conversions';
    public $timestamps = false;

    protected $fillable = [
        'from_uom_id',
        'to_uom_id',
        'factor',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'factor' => 'decimal:6',
            'status' => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function fromUom()
    {
        return $this->belongsTo(Uom::class, 'from_uom_id');
    }

    public function toUom()
    {
        return $this->belongsTo(Uom::class, 'to_uom_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }

    // ===== HELPERS =====

    /**
     * Hệ số quy đổi chiều ngược lại: to → from.
     */
    public function getInverseFactorAttribute(): float
    {
        return $this->factor != 0 ? round(1 / (float) $this->factor, 6) : 0;
    }

    /**
     * Tra cứu và tính số lượng sau quy đổi.
     * Tự thử chiều ngược nếu không tìm thấy chiều thuận.
     *
     * @return float|null  NULL nếu không tìm thấy conversion
     */
    public static function convert(int $fromUomId, int $toUomId, float $quantity): ?float
    {
        if ($fromUomId === $toUomId) {
            return $quantity;
        }

        $conversion = static::active()
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->first();

        if ($conversion) {
            return round($quantity * (float) $conversion->factor, 3);
        }

        // Thử chiều ngược lại
        $inverse = static::active()
            ->where('from_uom_id', $toUomId)
            ->where('to_uom_id', $fromUomId)
            ->first();

        if ($inverse && (float) $inverse->factor !== 0.0) {
            return round($quantity / (float) $inverse->factor, 3);
        }

        return null;
    }
}
