<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Models\Inventory\Serial;
use App\Repositories\Contracts\Inventory\SerialRepositoryInterface;
use Illuminate\Support\Collection;

class SerialRepository implements SerialRepositoryInterface
{
    public function firstOrCreate(string $serialNumber, array $attributes): Serial
    {
        return Serial::firstOrCreate(
            ['serial_number' => $serialNumber],
            $attributes
        );
    }

    public function find(int $id): ?Serial
    {
        return Serial::find($id);
    }

    public function findExistingSerialNumbers(array $serialNumbers, array $excludeLotIds = []): Collection
    {
        return Serial::whereIn('serial_number', $serialNumbers)
            ->whereNotIn('lot_id', $excludeLotIds)
            ->pluck('serial_number');
    }
}