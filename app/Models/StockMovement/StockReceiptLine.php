<?php

namespace App\Models\StockMovement;

use App\Models\Master\Product;
use App\Models\Master\Sn;
use App\Models\Master\Uom;
use Illuminate\Database\Eloquent\Model;

class StockReceiptLine extends Model
{
    protected $table = 'stock_receipt_line';

    protected $fillable = [
        'stock_receipt_id', 'product_id', 'uom_id', 'sn_id', 'expected_qty', 'note',
    ];

    protected $casts = [
        'stock_receipt_id' => 'integer',
        'product_id'       => 'integer',
        'uom_id'           => 'integer',
        'sn_id'            => 'integer',
        'expected_qty'     => 'decimal:3',
    ];

    // ===== RELATIONSHIPS =====

    public function receipt()
    {
        return $this->belongsTo(StockReceipt::class, 'stock_receipt_id');
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
        return $this->hasMany(StockReceiptDetail::class, 'stock_receipt_line_id');
    }
}