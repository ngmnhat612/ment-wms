<?php

namespace App\Repositories\Contracts\Master;

use App\Models\Master\UomConversion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UomConversionRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function totalCount(): int;

    public function activeCount(): int;

    public function activeUoms(): Collection;

    public function create(array $data): UomConversion;

    public function update(UomConversion $uomConversion, array $data): UomConversion;

    public function delete(UomConversion $uomConversion): bool;
}