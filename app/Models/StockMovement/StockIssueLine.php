<?php

namespace App\Models\StockMovement;

use App\Models\Master\Product;
use App\Models\Master\Sn;
use App\Models\Master\Uom;
use Illuminate\Database\Eloquent\Model;

class StockIssueLine extends Model
{
    protected $table = 'stock_issue_line';

    protected $fillable = [
        'stock_issue_id', 'product_id', 'uom_id', 'sn_id', 'expected_qty', 'note',
    ];

    protected $casts = [
        'stock_issue_id' => 'integer',
        'product_id'     => 'integer',
        'uom_id'         => 'integer',
        'sn_id'          => 'integer',
        'expected_qty'   => 'decimal:3',
    ];

    // ===== RELATIONSHIPS =====

    public function issue()
    {
        return $this->belongsTo(StockIssue::class, 'stock_issue_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function sn()
    {
        return $this->belongsTo(Sn::class, 'sn_id');
    }

    public function details()
    {
        return $this->hasMany(StockIssueDetail::class, 'stock_issue_line_id');
    }
}