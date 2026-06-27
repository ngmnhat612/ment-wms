<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Enums\ActiveStatus;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Category::with('parent');

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
        return Category::count();
    }

    public function activeCount(): int
    {
        return Category::where('status', ActiveStatus::Active->value)->count();
    }

    public function allActive(): Collection
    {
        return Category::active()->orderBy('name')->get();
    }

    public function getParentOptions(): Collection
    {
        return Category::where('status', ActiveStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function hasChildren(Category $category): bool
    {
        return $category->children()->exists();
    }

    public function hasProducts(Category $category): bool
    {
        return $category->products()->exists();
    }

    public function getDescendantIds(Category $category): array
    {
        $ids      = [];
        $children = $category->children()->select('id')->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            // Load đệ quy: eager-load children của child để tránh N+1
            $childFull = Category::with('children')->find($child->id);
            if ($childFull) {
                $ids = array_merge($ids, $this->getDescendantIds($childFull));
            }
        }

        return $ids;
    }
}