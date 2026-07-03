<?php

namespace App\Repositories\Eloquent;

use App\Enums\ActiveStatus;
use App\Models\Sn;
use App\Repositories\Contracts\SnRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SnRepository implements SnRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Sn::query();

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

    public function allActive(): Collection
    {
        return Sn::active()->orderBy('name')->get();
    }

    public function create(array $data): Sn
    {
        return Sn::create($data);
    }

    public function update(Sn $sn, array $data): bool
    {
        return $sn->update($data);
    }

    public function delete(Sn $sn): bool
    {
        return $sn->delete();
    }

}
