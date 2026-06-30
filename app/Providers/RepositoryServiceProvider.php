<?php

namespace App\Providers;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\UomRepositoryInterface;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Repositories\Contracts\ReorderRuleRepositoryInterface;
use App\Repositories\Contracts\PutawayRuleRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\UomRepository;
use App\Repositories\Eloquent\BrandRepository;
use App\Repositories\Eloquent\WarehouseRepository;
use App\Repositories\Eloquent\ReorderRuleRepository;
use App\Repositories\Eloquent\PutawayRuleRepository;
use App\Repositories\Eloquent\LocationRepository;
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
        
        // --- Thêm binding cho các repository khác tại đây ---
        // $this->app->bind(StockRepositoryInterface::class, StockRepository::class);
    }
}