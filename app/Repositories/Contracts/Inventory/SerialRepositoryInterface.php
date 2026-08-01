<?php

namespace App\Repositories\Contracts\Inventory;

use App\Models\Inventory\Serial;
use Illuminate\Support\Collection;

interface SerialRepositoryInterface
{
    public function firstOrCreate(int $productId, string $serialNumber, array $attributes): Serial;

    public function find(int $id): ?Serial;

    public function findExistingSerialNumbers(int $productId, array $serialNumbers, array $excludeLotIds = []): Collection;
}