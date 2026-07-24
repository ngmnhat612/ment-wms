<?php

namespace App\Repositories\Eloquent\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Supplier;
use App\Repositories\Contracts\Master\SupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierRepository implements SupplierRepositoryInterface
{
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Supplier::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('tax_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $sortable = ['code', 'name', 'tax_code'];
        $sortReq  = $filters['sort'] ?? '';
        $sortDir  = in_array($filters['dir'] ?? '', ['asc', 'desc']) ? $filters['dir'] : 'desc';

        if (in_array($sortReq, $sortable)) {
            $query->orderBy($sortReq, $sortDir);
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return Supplier::count();
    }

    public function activeCount(): int
    {
        return Supplier::where('status', ActiveStatus::Active->value)->count();
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): bool
    {
        return $supplier->update($data);
    }

    public function delete(Supplier $supplier): bool
    {
        return $supplier->delete();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return Supplier::where('code', $code)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
