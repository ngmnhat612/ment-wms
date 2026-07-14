<?php

namespace App\Models\Stocktake;

use App\Enums\InventoryCheckScope;
use App\Models\Master\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryFreezeDetail extends Model
{
    use SoftDeletes;

    protected $table = 'inventory_freeze_detail';

    protected $fillable = [
        'freeze_id', 'freeze_scope', 'location_id',
    ];

    protected function casts(): array
    {
        return [
            'freeze_id'    => 'integer',
            'freeze_scope' => InventoryCheckScope::class,
            'location_id'  => 'integer',
        ];
    }

    // ===== RELATIONSHIPS =====

    public function freeze()
    {
        return $this->belongsTo(InventoryFreeze::class, 'freeze_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
