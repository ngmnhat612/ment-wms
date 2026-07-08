<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Enums\LotSerialStatus;
use App\Enums\LocationType;
use App\Models\Inventory\Serial;
use App\Models\Inventory\Stock;
use App\Models\Master\Location;
use App\Repositories\Contracts\Inventory\StockRepositoryInterface;
use Illuminate\Support\Collection;

class StockRepository implements StockRepositoryInterface
{
    public function availableForIssue(int $productId): Collection
    {
        $stocks = Stock::with(['currentLocation', 'lot', 'serial'])
            ->where('product_id', $productId)
            ->whereHas('currentLocation', fn ($q) => $q->where('type', LocationType::Internal->value))
            ->where(fn ($q) => $q->where('available_qty', '>', 0)
                ->orWhereRaw('(quantity - reserved_qty) > 0'))
            ->get();

        return $stocks;
    }

    public function serialsInStock(int $productId, int $lotId): Collection
    {
        return Serial::where('product_id', $productId)
            ->where('lot_id', $lotId)
            ->where('status', LotSerialStatus::InStock->value)
            ->get();
    }
}