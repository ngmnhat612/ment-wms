<?php

namespace App\Repositories\Eloquent\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Location;
use App\Models\Master\Warehouse;
use App\Enums\LocationType;
use App\Repositories\Contracts\Master\WarehouseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Warehouse::with('manager');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $sortable = ['code', 'name'];
        $sortBy   = in_array($filters['sort'] ?? '', $sortable) ? $filters['sort'] : 'created_at';
        $sortDir  = in_array($filters['dir'] ?? '', ['asc', 'desc']) ? $filters['dir'] : 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function totalCount(): int
    {
        return Warehouse::count();
    }

    public function activeCount(): int
    {
        return Warehouse::where('status', ActiveStatus::Active->value)->count();
    }

    public function allActive(): Collection
    {
        return Warehouse::active()->orderBy('name')->get();
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): bool
    {
        return $warehouse->update($data);
    }

    public function delete(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return Warehouse::where('code', $code)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
