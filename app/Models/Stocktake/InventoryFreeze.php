<?php

namespace App\Models\Stocktake;

use App\Enums\InventoryCheckScope;
use App\Models\Master\Account;
use App\Models\Master\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryFreeze extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_freeze';

    protected $fillable = [
        'check_id', 'warehouse_id', 'check_type', 'frozen_by', 'frozen_at', 'unfrozen_at',
    ];

    protected function casts(): array
    {
        return [
            'check_id'     => 'integer',
            'warehouse_id' => 'integer',
            'check_type'   => InventoryCheckScope::class,
            'frozen_by'    => 'integer',
            'frozen_at'    => 'datetime',
            'unfrozen_at'  => 'datetime',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function inventoryCheck()
    {
        return $this->belongsTo(InventoryCheck::class, 'check_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function frozenBy()
    {
        return $this->belongsTo(Account::class, 'frozen_by');
    }

    public function details()
    {
        return $this->hasMany(InventoryFreezeDetail::class, 'freeze_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->whereNull('unfrozen_at');
    }

    // ===== HELPERS =====

    public function isActive(): bool
    {
        return is_null($this->unfrozen_at);
    }
}
