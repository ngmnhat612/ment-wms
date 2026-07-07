<?php

namespace App\Http\Controllers;

use App\Enums\LocationType;
use App\Enums\LotSerialStatus;
use App\Models\Master\Category;
use App\Models\Master\Location;
use App\Models\Master\Product;
use App\Models\Inventory\Stock;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Danh sách tồn kho hiện tại theo sản phẩm/vị trí/lô/serial.
     */
    public function index(Request $request)
    {
        $showVirtual = $request->boolean('show_virtual');
        $showZero    = $request->boolean('show_zero');

        $baseQuery = Stock::query()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('locations', 'locations.id', '=', 'stocks.current_location_id')
            ->when(! $showVirtual, fn ($q) => $q->where('locations.type', LocationType::Internal->value));

        // ── KPI cards ────────────────────────────────────────────────────
        $totalSkuCount = (clone $baseQuery)
            ->where('stocks.quantity', '>', 0)
            ->distinct()
            ->count('stocks.product_id');

        $totalQty        = (float) (clone $baseQuery)->sum('stocks.quantity');
        $reservedQty     = (float) (clone $baseQuery)->sum('stocks.reserved_qty');
        $quarantineCount = (clone $baseQuery)
            ->where('stocks.status', LotSerialStatus::Expired->value)
            ->count();

        // ── Danh sách (có filter + phân trang) ──────────────────────────
        $query = $baseQuery
            ->clone()
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('uoms', 'uoms.id', '=', 'products.uom_id')
            ->leftJoin('lots', 'lots.id', '=', 'stocks.lot_id')
            ->leftJoin('serials', 'serials.id', '=', 'stocks.serial_id')
            ->leftJoin('reorder_rules', function ($join) {
                $join->on('reorder_rules.product_id', '=', 'stocks.product_id')
                    ->on('reorder_rules.warehouse_id', '=', 'stocks.warehouse_id');
            })
            ->select([
                'stocks.id',
                'stocks.product_id',
                'stocks.lot_id',
                'stocks.serial_id',
                'stocks.quantity',
                'stocks.reserved_qty',
                'stocks.available_qty',
                'stocks.status',
                'products.code as product_code',
                'products.name as product_name',
                'categories.name as category_name',
                'locations.code as location_code',
                'locations.name as location_name',
                'uoms.name as uom_name',
                'lots.lot_number',
                'lots.expiry_date',
                'serials.serial_number',
                'reorder_rules.min_qty as min_stock',
            ]);

        if (! $showZero) {
            $query->where('stocks.quantity', '>', 0);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('products.code', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
                    ->orWhere('lots.lot_number', 'like', "%{$search}%")
                    ->orWhere('serials.serial_number', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('products.category_id', $categoryId);
        }

        if ($productId = $request->get('product_id')) {
            $query->where('stocks.product_id', $productId);
        }

        if ($locationId = $request->get('location_id')) {
            $query->where('stocks.current_location_id', $locationId);
        }

        if ($status = $request->get('status')) {
            $query->where('stocks.status', $status);
        }

        $stocks = $query
            ->orderBy('products.code')
            ->orderBy('locations.code')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.index', [
            'stocks'          => $stocks,
            'categories'      => Category::orderBy('name')->get(),
            'products'        => Product::orderBy('name')->get(),
            'locations'       => Location::orderBy('code')->get(),
            'totalSkuCount'   => $totalSkuCount,
            'totalQty'        => $totalQty,
            'reservedQty'     => $reservedQty,
            'quarantineCount' => $quarantineCount,
        ]);
    }

    /**
     * Lịch sử giao dịch tồn kho (transaction ledger).
     *
     * NOTE: view resources/views/inventory/ledger.blade.php hiện đang trống
     * (chưa được dựng) — đây chỉ là chỗ giữ route để trang index không vỡ
     * khi bấm nút "Lịch sử giao dịch". Cần xây dựng riêng nếu muốn dùng thật.
     */
    public function ledger(Request $request)
    {
        return view('inventory.ledger');
    }
}