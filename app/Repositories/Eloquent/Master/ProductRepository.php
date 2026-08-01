<?php

namespace App\Repositories\Eloquent\Master;

use App\Models\Master\Product;
use App\Enums\ActiveStatus;
use App\Repositories\Contracts\Master\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with([
            'category',
            'uom',
            'reorderRule',
            'putawayRule.destinationLocation',
            'category.putawayRule.destinationLocation',
        ]);

        $query = Product::with(['category', 'uom']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['search_spec'])) {
            $searchSpec = $filters['search_spec'];
            $query->where('specification', 'like', "%{$searchSpec}%");
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['tracking_type'])) {
            $query->where('tracking_type', $filters['tracking_type']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $sortable = ['code', 'name', 'specification'];
        $sortReq  = $filters['sort'] ?? '';
        $sortBy   = in_array($sortReq, $sortable) ? $sortReq : 'created_at';
        $sortDir  = in_array($filters['dir'] ?? '', ['asc', 'desc']) ? $filters['dir'] : 'desc';

        return $query
            ->orderBy($sortBy, $sortDir)
            ->orderBy('id', $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function totalCount(): int
    {
        return Product::count();
    }

    public function activeCount(): int
    {
        return Product::where('status', ActiveStatus::Active->value)->count();
    }

    public function findById(int $id, array $with = []): ?Product
    {
        return Product::with($with)->find($id);
    }

    public function findManyByIds(array $ids): Collection
    {
        return Product::whereIn('id', $ids)->get()->keyBy('id');
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    public function barcodeExists(string $barcode, ?int $excludeId = null): bool
    {
        return Product::where('barcode', $barcode)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return Product::where('code', $code)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function allRootActive(): Collection
    {
        return Product::select('id', 'code', 'name')
            // ->where('status', ActiveStatus::Active)
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();
    }

    public function allOrdered(): Collection
    {
        return Product::select('id', 'code', 'name')
            ->orderBy('name')
            ->get();
    }

    public function findRootByCode(string $code): ?Product
    {
        return Product::where('code', $code)
            ->whereNull('parent_id')
            ->first();
    }
}
