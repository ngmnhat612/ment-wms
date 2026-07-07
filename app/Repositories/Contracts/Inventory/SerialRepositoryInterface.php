<?php

namespace App\Repositories\Contracts\Inventory;

use App\Models\Inventory\Serial;

interface SerialRepositoryInterface
{
    public function firstOrCreate(string $serialNumber, array $attributes): Serial;

    public function find(int $id): ?Serial;
}