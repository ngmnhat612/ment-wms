<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Employee;
use App\Models\InventoryCheck;
use App\Models\Product;
use App\Models\PutawayRule;
use App\Models\ReorderRule;
use App\Models\StockAdjustment;
use App\Models\StockIssue;
use App\Models\StockReceipt;
use App\Models\StockRequest;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Models\Brand;

use App\Policies\AccountPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\InventoryCheckPolicy;
use App\Policies\InventoryFreezePolicy;
use App\Policies\ProductPolicy;
use App\Policies\PutawayRulePolicy;
use App\Policies\ReorderRulePolicy;
use App\Policies\StockAdjustmentPolicy;
use App\Policies\StockIssuePolicy;
use App\Policies\StockReceiptPolicy;
use App\Policies\StockRequestPolicy;
use App\Policies\UomPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\BrandPolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // Master
        // Account::class      => AccountPolicy::class,
        // Employee::class     => EmployeePolicy::class,
        Category::class     => CategoryPolicy::class,
        Product::class      => ProductPolicy::class,
        Uom::class          => UomPolicy::class,
        Brand::class        => BrandPolicy::class,
        Warehouse::class    => WarehousePolicy::class,
        ReorderRule::class  => ReorderRulePolicy::class,
        PutawayRule::class  => PutawayRulePolicy::class,

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