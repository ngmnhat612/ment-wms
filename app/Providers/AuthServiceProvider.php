<?php

namespace App\Providers;

use App\Models\Master\Account;
use App\Models\Master\Category;
use App\Models\Master\Employee;
use App\Models\InventoryCheck;
use App\Models\Master\Product;
use App\Models\Master\PutawayRule;
use App\Models\Master\ReorderRule;
use App\Models\StockAdjustment;
use App\Models\StockIssue;
use App\Models\StockReceipt;
use App\Models\StockRequest;
use App\Models\Master\Uom;
use App\Models\Master\Warehouse;
use App\Models\Master\Brand;
use App\Models\Master\Location;
use App\Models\Master\Supplier;
use App\Models\Master\Department;
use App\Models\Master\Sn;

use App\Policies\Master\AccountPolicy;
use App\Policies\Master\CategoryPolicy;
use App\Policies\Master\EmployeePolicy;
use App\Policies\Master\InventoryCheckPolicy;
use App\Policies\Master\InventoryFreezePolicy;
use App\Policies\Master\ProductPolicy;
use App\Policies\Master\PutawayRulePolicy;
use App\Policies\Master\ReorderRulePolicy;
use App\Policies\Master\StockAdjustmentPolicy;
use App\Policies\Master\StockIssuePolicy;
use App\Policies\Master\StockReceiptPolicy;
use App\Policies\Master\StockRequestPolicy;
use App\Policies\Master\UomPolicy;
use App\Policies\Master\WarehousePolicy;
use App\Policies\Master\BrandPolicy;
use App\Policies\Master\LocationPolicy;
use App\Policies\Master\SupplierPolicy;
use App\Policies\Master\DepartmentPolicy;
use App\Policies\Master\SnPolicy;
    
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

        // Inbound / Outbound
        // StockReceipt::class => StockReceiptPolicy::class,
        // StockIssue::class   => StockIssuePolicy::class,
        // StockRequest::class => StockRequestPolicy::class,

        // Stocktake
        // InventoryCheck::class      => InventoryCheckPolicy::class,
        // StockAdjustment::class     => StockAdjustmentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}