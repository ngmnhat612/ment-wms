<?php

use Illuminate\Support\Facades\Route;

// ── AUTH ──────────────────────────────────────────────────────────────────────
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// ── CORE ──────────────────────────────────────────────────────────────────────
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportAlertController;

// ── MASTER DATA ───────────────────────────────────────────────────────────────
use App\Http\Controllers\Master\UomController;
use App\Http\Controllers\Master\UomConversionController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\BrandController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\EmployeeController;
use App\Http\Controllers\Master\AccountController;
use App\Http\Controllers\Master\WarehouseController;
use App\Http\Controllers\Master\WarehouseEmployeeController;
use App\Http\Controllers\Master\LocationController;
use App\Http\Controllers\Master\ProductController;
use App\Http\Controllers\Master\ReorderRuleController;

// ── INBOUND ───────────────────────────────────────────────────────────────────
use App\Http\Controllers\Inbound\StockReceiptController;

// ── OUTBOUND ──────────────────────────────────────────────────────────────────
use App\Http\Controllers\Outbound\StockIssueController;
use App\Http\Controllers\Outbound\StockRequestController;

// ── STOCKTAKE ─────────────────────────────────────────────────────────────────
use App\Http\Controllers\Stocktake\InventoryCheckController;
use App\Http\Controllers\Stocktake\InventoryFreezeController;
use App\Http\Controllers\Stocktake\StockAdjustmentController;

// ─────────────────────────────────────────────────────────────────────────────

Route::get('/', fn() => redirect()->route('dashboard'));

Route::middleware('auth')->group(function () {

    // ── DASHBOARD ─────────────────────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── MASTER DATA ───────────────────────────────────────────────────────────
    Route::prefix('master')->name('master.')->group(function () {

        // Đơn vị tính
        Route::resource('uom', UomController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // // Quy đổi đơn vị
        // Route::resource('uom-conversion', UomConversionController::class)
        //     ->only(['index', 'store', 'update', 'destroy']);

        // Danh mục vật tư
        Route::resource('category', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // // Nhà cung cấp
        // Route::resource('supplier', SupplierController::class)
        //     ->only(['index', 'store', 'update', 'destroy']);

        // // Nhân viên
        // Route::resource('employee', EmployeeController::class)
        //     ->only(['index', 'store', 'update', 'destroy']);

        // // Vị trí
        // Route::resource('location', LocationController::class)
        //     ->only(['index', 'store', 'update', 'destroy']);

        // Vật tư
        Route::get('product/find', [ProductController::class, 'find'])
            ->name('product.find');
        Route::post('product/variant', [ProductController::class, 'storeVariant'])
            ->name('product.storeVariant');
        Route::resource('product', ProductController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // // Quy tắc tái đặt hàng
        // Route::resource('reorder-rule', ReorderRuleController::class)
        //     ->only(['index', 'store', 'update', 'destroy']);

        // Thương hiệu
        Route::resource('brand', BrandController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // // Kho
        // Route::resource('warehouse', WarehouseController::class)
        //     ->only(['index', 'store', 'update', 'destroy']);
    });

    Route::get('under-construction', fn() => view('under-construction'))
        ->name('under-construction');

    // ── PENDING — trỏ tạm về under-construction ───────────────────────────────
    $pending = [
        // Nghiệp vụ kho
        'stock-requests.index'  => 'stock-requests',
        'receipts.index'        => 'receipts',
        'issues.index'          => 'issues',
        'stocktakes.index'      => 'stocktakes',

        // Tồn kho
        'inventory.index'       => 'inventory',
        'inventory.locations'   => 'inventory/locations',

        // Master data
        // 'master.uom.index'          => 'master/uom',
        'master.uom-conversion.index' => 'master/uom-conversion',
        // 'master.category.index'     => 'master/category',
        'master.supplier.index'     => 'master/supplier',
        'master.employee.index'     => 'master/employee',
        'master.location.index'     => 'master/location',
        'master.reorder-rule.index' => 'master/reorder-rule',
        'master.putaway-rule.index' => 'master/putaway-rule',
        // 'master.brand.index'        => 'master/brand',
        'master.warehouse.index'    => 'master/warehouse',


        // Báo cáo
        'reports.index'                  => 'reports',
        'reports.alerts.below_min'       => 'reports/alerts/below-min',
        'reports.alerts.slow_moving'     => 'reports/alerts/slow-moving',
        'reports.alerts.near_expiry'     => 'reports/alerts/near-expiry',

        // Nhật ký
        'transaction-log.index' => 'transaction-log',
        'activity-log.index'    => 'activity-log',
    ];

    foreach ($pending as $name => $uri) {
        Route::get($uri, fn() => view('under-construction', ['feature' => $name]))
            ->name($name);
    }

});

require __DIR__ . '/auth.php';