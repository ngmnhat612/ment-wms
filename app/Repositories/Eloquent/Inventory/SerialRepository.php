<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Models\Inventory\Serial;
use App\Repositories\Contracts\Inventory\SerialRepositoryInterface;
use Illuminate\Support\Collection;

class SerialRepository implements SerialRepositoryInterface
{
    public function firstOrCreate(int $productId, string $serialNumber, array $attributes): Serial
    {
        return Serial::firstOrCreate(
            ['product_id' => $productId, 'serial_number' => $serialNumber],
            array_merge($attributes, ['product_id' => $productId])
        );
    }

    public function find(int $id): ?Serial
    {
        return Serial::find($id);
    }

    public function findExistingSerialNumbers(int $productId, array $serialNumbers, array $excludeLotIds = []): Collection
    {
        return Serial::where('product_id', $productId)
            ->whereIn('serial_number', $serialNumbers)
            ->whereNotIn('lot_id', $excludeLotIds)
            ->pluck('serial_number');
    }
}