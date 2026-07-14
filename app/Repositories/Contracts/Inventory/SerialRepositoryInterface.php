<?php

namespace App\Repositories\Contracts\Inventory;

use App\Models\Inventory\Serial;
use Illuminate\Support\Collection;

interface SerialRepositoryInterface
{
    public function firstOrCreate(string $serialNumber, array $attributes): Serial;

    public function find(int $id): ?Serial;

    public function findExistingSerialNumbers(array $serialNumbers, array $excludeLotIds = []): Collection;
}