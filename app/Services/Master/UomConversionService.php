<?php

namespace App\Services\Master;

use App\Models\Master\UomConversion;
use App\Repositories\Contracts\Master\UomConversionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UomConversionService
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $uomConversionRepository,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->uomConversionRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->uomConversionRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->uomConversionRepository->activeCount();
    }

    public function activeUoms(): Collection
    {
        return $this->uomConversionRepository->activeUoms();
    }

    // ===== WRITE =====

    public function create(array $data): UomConversion
    {
        return $this->uomConversionRepository->create($data);
    }

    public function update(UomConversion $uomConversion, array $data): UomConversion
    {
        return $this->uomConversionRepository->update($uomConversion, $data);
    }

    public function delete(UomConversion $uomConversion): bool
    {
        return $this->uomConversionRepository->delete($uomConversion);
    }
}