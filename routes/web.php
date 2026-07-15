<?php

use Illuminate\Support\Facades\Route;

// ── AUTH ──────────────────────────────────────────────────────────────────────
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// ── CORE ──────────────────────────────────────────────────────────────────────
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Inventory\InventoryController;
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
use App\Http\Controllers\Master\PutawayRuleController;
use App\Http\Controllers\Master\DepartmentController;
use App\Http\Controllers\Master\SnController;

// ── STOCK-MOVEMENT ────────────────────────────────────────────────────────────
use App\Http\Controllers\StockMovement\StockMovementController;
use App\Http\Controllers\StockMovement\StockReceiptController;
use App\Http\Controllers\StockMovement\StockIssueController;

// ── STOCK-REQUEST ─────────────────────────────────────────────────────────────
use App\Http\Controllers\StockRequest\StockRequestController;
use App\Http\Controllers\StockRequest\StockInRequestController;
use App\Http\Controllers\StockRequest\StockOutRequestController;

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

        // Danh mục vật tư
        Route::resource('category', CategoryController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Nhà cung cấp
        Route::resource('supplier', SupplierController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Nhân viên
        Route::resource('employee', EmployeeController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        // Tài khoản đăng nhập của nhân viên
        Route::post('employee/{employee}/account', [AccountController::class, 'store'])
            ->name('employee.account.store');
        Route::put('employee/{employee}/account', [AccountController::class, 'update'])
            ->name('employee.account.update');
        Route::delete('employee/{employee}/account', [AccountController::class, 'destroy'])
            ->name('employee.account.destroy');

        // Vị trí
        Route::resource('location', LocationController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Vật tư
        Route::get('product/find', [ProductController::class, 'find'])
            ->name('product.find');
        Route::post('product/variant', [ProductController::class, 'storeVariant'])
            ->name('product.storeVariant');
        Route::resource('product', ProductController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Quy tắc tái đặt hàng
        Route::resource('reorder-rule', ReorderRuleController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Quy tắc tái đặt hàng
        Route::resource('putaway-rule', PutawayRuleController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Thương hiệu
        Route::resource('brand', BrandController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Kho
        Route::resource('warehouse', WarehouseController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        
        // Bộ phận
        Route::resource('department', DepartmentController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // Dự án
        Route::resource('sn', SnController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // // Quy đổi đơn vị
        // Route::resource('uom-conversion', UomConversionController::class)
        //     ->only(['index', 'store', 'update', 'destroy']);
    });

    // ── NHẬP / XUẤT KHO (trang gộp) ─────────────────────────────────────────────
    Route::get('stock-movements', [StockMovementController::class, 'index'])
        ->name('stock-movements.index');

    // ── NHẬP KHO ─────────────────────────────────────────────────────────────
    Route::resource('receipts', StockReceiptController::class)->except(['index']);
    Route::get('receipts/{receipt}/print', [StockReceiptController::class, 'printPdf'])
        ->name('receipts.print');
    Route::post('receipts/{receipt}/approve', [StockReceiptController::class, 'approve'])
        ->name('receipts.approve');
    Route::post('receipts/{receipt}/cancel', [StockReceiptController::class, 'cancel'])
        ->name('receipts.cancel');
    Route::post('receipts/suggest-putaway', [StockReceiptController::class, 'suggestPutaway'])
        ->name('receipts.suggest-putaway');

    // ── XUẤT KHO ─────────────────────────────────────────────────────────────
    Route::get('issues/stock-locations/{product}', [StockIssueController::class, 'stockLocations'])
        ->name('issues.stock-locations');
    Route::resource('issues', StockIssueController::class)->except(['index']);
    Route::get('issues/{issue}/print', [StockIssueController::class, 'printPdf'])
        ->name('issues.print');
    Route::post('issues/{issue}/complete', [StockIssueController::class, 'complete'])
        ->name('issues.complete');
    Route::post('issues/{issue}/cancel', [StockIssueController::class, 'cancel'])
        ->name('issues.cancel');

    // ── TỒN KHO ──────────────────────────────────────────────────────────────
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/lot-serials', [InventoryController::class, 'lotSerials'])->name('inventory.lotSerials');
    Route::post('inventory/update-location', [InventoryController::class, 'updateLocation'])->name('inventory.updateLocation');

    // ── YÊU CẦU NHẬP / XUẤT KHO ──────────────────────────────────────────────
    Route::get('stock-requests', [StockRequestController::class, 'index'])
        ->name('stock-requests.index');
 
    // ── YÊU CẦU NHẬP KHO ─────────────────────────────────────────────────────
    Route::resource('stock-in-requests', StockInRequestController::class)->except(['index']);
    Route::post('stock-in-requests/{stock_in_request}/complete', [StockInRequestController::class, 'complete'])
        ->name('stock-in-requests.complete');
    Route::post('stock-in-requests/{stock_in_request}/cancel', [StockInRequestController::class, 'cancel'])
        ->name('stock-in-requests.cancel');
 
    // ── YÊU CẦU XUẤT KHO ─────────────────────────────────────────────────────
    Route::resource('stock-out-requests', StockOutRequestController::class)->except(['index']);
    Route::post('stock-out-requests/{stock_out_request}/complete', [StockOutRequestController::class, 'complete'])
        ->name('stock-out-requests.complete');
    Route::post('stock-out-requests/{stock_out_request}/cancel', [StockOutRequestController::class, 'cancel'])
        ->name('stock-out-requests.cancel');

    // ── KIỂM KÊ KHO ──────────────────────────────────────────────────────────
    Route::resource('stocktakes', InventoryCheckController::class)->except(['destroy']);
 
    Route::post('stocktakes/{stocktake}/start', [InventoryCheckController::class, 'start'])
        ->name('stocktakes.start');
    Route::post('stocktakes/{stocktake}/complete', [InventoryCheckController::class, 'complete'])
        ->name('stocktakes.complete');
    Route::post('stocktakes/{stocktake}/cancel', [InventoryCheckController::class, 'cancel'])
        ->name('stocktakes.cancel');
    Route::put('stocktakes/{stocktake}/details', [InventoryCheckController::class, 'updateDetails'])
        ->name('stocktakes.details.update');
 
    // ── PHIẾU ĐIỀU CHỈNH ──────────────────────────────────────────────────────
    Route::get('stocktakes/{stocktake}/adjustment/create', [StockAdjustmentController::class, 'create'])
        ->name('stocktakes.adjustment.create');
    Route::post('stocktakes/{stocktake}/adjustment', [StockAdjustmentController::class, 'store'])
        ->name('stocktakes.adjustment.store');
    Route::get('stocktakes/{stocktake}/adjustment/{adjustment}/edit', [StockAdjustmentController::class, 'edit'])
        ->name('stocktakes.adjustment.edit');
    Route::put('stocktakes/{stocktake}/adjustment/{adjustment}', [StockAdjustmentController::class, 'update'])
        ->name('stocktakes.adjustment.update');
    Route::get('stocktakes/{stocktake}/adjustment/{adjustment}', [StockAdjustmentController::class, 'show'])
        ->name('stocktakes.adjustment.show');
    Route::post('stocktakes/{stocktake}/adjustment/{adjustment}/complete', [StockAdjustmentController::class, 'complete'])
        ->name('stocktakes.adjustment.complete');
    Route::post('stocktakes/{stocktake}/adjustment/{adjustment}/cancel', [StockAdjustmentController::class, 'cancel'])
        ->name('stocktakes.adjustment.cancel');

    Route::get('under-construction', fn() => view('under-construction'))
        ->name('under-construction');

    // ── PENDING — trỏ tạm về under-construction ───────────────────────────────
    $pending = [
        // Nghiệp vụ kho
        // 'stocktakes.index'      => 'stocktakes',

        // Tồn kho
        'master.uom-conversion.index' => 'master/uom-conversion',


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