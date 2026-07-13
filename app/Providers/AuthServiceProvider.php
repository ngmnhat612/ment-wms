<?php

namespace App\Providers;

use App\Models\Master\Account;
use App\Models\Master\Category;
use App\Models\Master\Employee;
use App\Models\Master\Product;
use App\Models\Master\PutawayRule;
use App\Models\Master\ReorderRule;
use App\Models\Master\Uom;
use App\Models\Master\Warehouse;
use App\Models\Master\Brand;
use App\Models\Master\Location;
use App\Models\Master\Supplier;
use App\Models\Master\Department;
use App\Models\Master\Sn;
use App\Models\StockMovement\StockIssue;
use App\Models\StockMovement\StockReceipt;
use App\Models\Inventory\Stock;
use App\Models\StockRequest\StockInRequest;
use App\Models\StockRequest\StockOutRequest;
use App\Models\Stocktake\InventoryCheck;
use App\Models\Stocktake\InventoryFreeze;
use App\Models\Stocktake\StockAdjustment;
use App\Models\Master\UomConversion;
use App\Models\Master\WarehouseEmployee;

use App\Policies\Master\AccountPolicy;
use App\Policies\Master\CategoryPolicy;
use App\Policies\Master\EmployeePolicy;
use App\Policies\Master\ProductPolicy;
use App\Policies\Master\PutawayRulePolicy;
use App\Policies\Master\ReorderRulePolicy;
use App\Policies\Master\UomPolicy;
use App\Policies\Master\WarehousePolicy;
use App\Policies\Master\BrandPolicy;
use App\Policies\Master\LocationPolicy;
use App\Policies\Master\SupplierPolicy;
use App\Policies\Master\DepartmentPolicy;
use App\Policies\Master\SnPolicy;
use App\Policies\StockMovement\StockIssuePolicy;
use App\Policies\StockMovement\StockReceiptPolicy;
use App\Policies\Inventory\StockPolicy;
use App\Policies\StockRequest\StockInRequestPolicy;
use App\Policies\StockRequest\StockOutRequestPolicy;
use App\Policies\Stocktake\InventoryCheckPolicy;
use App\Policies\Stocktake\InventoryFreezePolicy;
use App\Policies\Stocktake\StockAdjustmentPolicy;
use App\Policies\Master\UomConversionPolicy;
use App\Policies\Master\WarehouseEmployeePolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Master
        Account::class      => AccountPolicy::class,
        Employee::class     => EmployeePolicy::class,
        Category::class     => CategoryPolicy::class,
        Product::class      => ProductPolicy::class,
        Uom::class          => UomPolicy::class,
        Brand::class        => BrandPolicy::class,
        Warehouse::class    => WarehousePolicy::class,
        ReorderRule::class  => ReorderRulePolicy::class,
        PutawayRule::class  => PutawayRulePolicy::class,
        Location::class     => LocationPolicy::class,
        Supplier::class     => SupplierPolicy::class,
        Department::class   => DepartmentPolicy::class,
        Sn::class           => SnPolicy::class,
        UomConversion::class     => UomConversionPolicy::class,
        WarehouseEmployee::class => WarehouseEmployeePolicy::class,

        // StockMovement
        StockReceipt::class => StockReceiptPolicy::class,
        StockIssue::class   => StockIssuePolicy::class,

        // Inventory
        Stock::class => StockPolicy::class,

        // StockRequest
        StockInRequest::class  => StockInRequestPolicy::class,
        StockOutRequest::class => StockOutRequestPolicy::class,

        // Stocktake
        InventoryCheck::class  => InventoryCheckPolicy::class,
        InventoryFreeze::class => InventoryFreezePolicy::class,
        StockAdjustment::class => StockAdjustmentPolicy::class,
        
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}