<?php

namespace App\Repositories\Eloquent;

use App\Enums\ActiveStatus;
use App\Models\Uom;
use App\Repositories\Contracts\UomRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UomRepository implements UomRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Uom::query();

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

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return Uom::count();
    }

    public function activeCount(): int
    {
        return Uom::where('status', ActiveStatus::Active->value)->count();
    }

    public function allActive(): Collection
    {
        return Uom::active()->orderBy('name')->get();
    }

    public function create(array $data): Uom
    {
        return Uom::create($data);
    }

    public function update(Uom $uom, array $data): bool
    {
        return $uom->update($data);
    }

    public function delete(Uom $uom): bool
    {
        return $uom->delete();
    }

    public function hasProducts(Uom $uom): bool
    {
        return $uom->products()->exists();
    }

    public function hasConversions(Uom $uom): bool
    {
        return $uom->conversionsFrom()->exists()
            || $uom->conversionsTo()->exists();
    }
}