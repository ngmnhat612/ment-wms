<?php

namespace App\Providers;

use App\Repositories\Contracts\Master\CategoryRepositoryInterface;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;
use App\Repositories\Contracts\Master\UomRepositoryInterface;
use App\Repositories\Contracts\Master\BrandRepositoryInterface;
use App\Repositories\Contracts\Master\WarehouseRepositoryInterface;
use App\Repositories\Contracts\Master\ReorderRuleRepositoryInterface;
use App\Repositories\Contracts\Master\PutawayRuleRepositoryInterface;
use App\Repositories\Contracts\Master\LocationRepositoryInterface;
use App\Repositories\Contracts\Master\SupplierRepositoryInterface;
use App\Repositories\Contracts\Master\DepartmentRepositoryInterface;
use App\Repositories\Contracts\Master\EmployeeRepositoryInterface;
use App\Repositories\Contracts\Master\AccountRepositoryInterface;
use App\Repositories\Contracts\Master\SnRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockReceiptRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockIssueRepositoryInterface;
use App\Repositories\Contracts\StockMovement\StockMovementFormDataRepositoryInterface;
use App\Repositories\Contracts\Inventory\LotRepositoryInterface;
use App\Repositories\Contracts\Inventory\SerialRepositoryInterface;
use App\Repositories\Contracts\Inventory\StockRepositoryInterface;
use App\Repositories\Contracts\StockRequest\StockInRequestRepositoryInterface;
use App\Repositories\Contracts\StockRequest\StockOutRequestRepositoryInterface;
use App\Repositories\Contracts\Stocktake\InventoryCheckRepositoryInterface;
use App\Repositories\Contracts\Stocktake\InventoryFreezeRepositoryInterface;
use App\Repositories\Contracts\Stocktake\StockAdjustmentRepositoryInterface;
use App\Repositories\Contracts\Master\UomConversionRepositoryInterface;
use App\Repositories\Contracts\Master\WarehouseEmployeeRepositoryInterface;
use App\Repositories\Contracts\Master\PutawayRuleFormDataRepositoryInterface;
use App\Repositories\Contracts\Master\ReorderRuleFormDataRepositoryInterface;

use App\Repositories\Eloquent\Master\CategoryRepository;
use App\Repositories\Eloquent\Master\ProductRepository;
use App\Repositories\Eloquent\Master\UomRepository;
use App\Repositories\Eloquent\Master\BrandRepository;
use App\Repositories\Eloquent\Master\WarehouseRepository;
use App\Repositories\Eloquent\Master\ReorderRuleRepository;
use App\Repositories\Eloquent\Master\PutawayRuleRepository;
use App\Repositories\Eloquent\Master\LocationRepository;
use App\Repositories\Eloquent\Master\SupplierRepository;
use App\Repositories\Eloquent\Master\DepartmentRepository;
use App\Repositories\Eloquent\Master\EmployeeRepository;
use App\Repositories\Eloquent\Master\AccountRepository;
use App\Repositories\Eloquent\Master\SnRepository;
use App\Repositories\Eloquent\StockMovement\StockReceiptRepository;
use App\Repositories\Eloquent\StockMovement\StockIssueRepository;
use App\Repositories\Eloquent\StockMovement\StockMovementFormDataRepository;
use App\Repositories\Eloquent\Inventory\LotRepository;
use App\Repositories\Eloquent\Inventory\SerialRepository;
use App\Repositories\Eloquent\Inventory\StockRepository;
use App\Repositories\Eloquent\StockRequest\StockInRequestRepository;
use App\Repositories\Eloquent\StockRequest\StockOutRequestRepository;
use App\Repositories\Eloquent\Stocktake\InventoryCheckRepository;
use App\Repositories\Eloquent\Stocktake\InventoryFreezeRepository;
use App\Repositories\Eloquent\Stocktake\StockAdjustmentRepository;
use App\Repositories\Eloquent\Master\UomConversionRepository;
use App\Repositories\Eloquent\Master\WarehouseEmployeeRepository;
use App\Repositories\Eloquent\Master\PutawayRuleFormDataRepository;
use App\Repositories\Eloquent\Master\ReorderRuleFormDataRepository;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductRepositoryInterface::class,
            ProductRepository::class,
        );

        $this->app->bind(
            CategoryRepositoryInterface::class,
            CategoryRepository::class,
        );

        $this->app->bind(
            UomRepositoryInterface::class,
            UomRepository::class,
        );

        $this->app->bind(
            BrandRepositoryInterface::class,
            BrandRepository::class,
        );

        $this->app->bind(
            WarehouseRepositoryInterface::class,
            WarehouseRepository::class,
        );

        $this->app->bind(
            ReorderRuleRepositoryInterface::class,
            ReorderRuleRepository::class,
        );

        $this->app->bind(
            PutawayRuleRepositoryInterface::class,
            PutawayRuleRepository::class,
        );

        $this->app->bind(
            LocationRepositoryInterface::class,
            LocationRepository::class
        );

        $this->app->bind(
            SupplierRepositoryInterface::class,
            SupplierRepository::class,
        );

        $this->app->bind(
            DepartmentRepositoryInterface::class,
            DepartmentRepository::class,
        );
        
        $this->app->bind(
            EmployeeRepositoryInterface::class,
            EmployeeRepository::class,
        );

        $this->app->bind(
            AccountRepositoryInterface::class,
            AccountRepository::class,
        );

        $this->app->bind(
            SnRepositoryInterface::class,
            SnRepository::class,
        );
        
        $this->app->bind(
            StockReceiptRepositoryInterface::class,
            StockReceiptRepository::class,
        );

        $this->app->bind(
            StockIssueRepositoryInterface::class,
            StockIssueRepository::class,
        );

        $this->app->bind(
            StockMovementFormDataRepositoryInterface::class,
            StockMovementFormDataRepository::class
        );

        $this->app->bind(
            LotRepositoryInterface::class, 
            LotRepository::class
        );

        $this->app->bind(
            SerialRepositoryInterface::class, 
            SerialRepository::class
        );

        $this->app->bind(
            StockRepositoryInterface::class,
            StockRepository::class
        );

        $this->app->bind(
            StockInRequestRepositoryInterface::class,
            StockInRequestRepository::class,
        );
 
        $this->app->bind(
            StockOutRequestRepositoryInterface::class,
            StockOutRequestRepository::class,
        );

        $this->app->bind(
            InventoryCheckRepositoryInterface::class,
            InventoryCheckRepository::class,
        );
    
        $this->app->bind(
            InventoryFreezeRepositoryInterface::class,
            InventoryFreezeRepository::class,
        );
    
        $this->app->bind(
            StockAdjustmentRepositoryInterface::class,
            StockAdjustmentRepository::class,
        );

        $this->app->bind(
            UomConversionRepositoryInterface::class,
            UomConversionRepository::class,
        );

        $this->app->bind(
            WarehouseEmployeeRepositoryInterface::class,
            WarehouseEmployeeRepository::class,
        );

        $this->app->bind(
            PutawayRuleFormDataRepositoryInterface::class,
            PutawayRuleFormDataRepository::class,
        );

        $this->app->bind(
            ReorderRuleFormDataRepositoryInterface::class,
            ReorderRuleFormDataRepository::class,
        );
    }
}