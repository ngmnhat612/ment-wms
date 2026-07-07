<?php

namespace App\Repositories\Eloquent\Inventory;

use App\Models\Inventory\Serial;
use App\Repositories\Contracts\Inventory\SerialRepositoryInterface;

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
}