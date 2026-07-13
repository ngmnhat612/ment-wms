<?php

namespace App\Repositories\Eloquent\Master;

use App\Models\Master\Uom;
use App\Models\Master\UomConversion;
use App\Repositories\Contracts\Master\UomConversionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UomConversionRepository implements UomConversionRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = UomConversion::with(['fromUom', 'toUom']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('fromUom', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('toUom', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('from_uom_id')->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return UomConversion::count();
    }

    public function activeCount(): int
    {
        return UomConversion::where('status', 1)->count();
    }

    public function activeUoms(): Collection
    {
        return Uom::where('status', 1)->orderBy('name')->get();
    }

    public function create(array $data): UomConversion
    {
        return UomConversion::create($data);
    }

    public function update(UomConversion $uomConversion, array $data): UomConversion
    {
        $uomConversion->update($data);

        return $uomConversion->fresh();
    }

    public function delete(UomConversion $uomConversion): bool
    {
        return (bool) $uomConversion->delete();
    }
}