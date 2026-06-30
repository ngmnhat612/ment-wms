<?php

namespace App\Repositories\Eloquent;

use App\Enums\ActiveStatus;
use App\Enums\LocationType;
use App\Models\Location;
use App\Models\Warehouse;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;


class LocationRepository implements LocationRepositoryInterface
{
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Location::with(['parent', 'warehouse'])
                 ->where('type', LocationType::Internal->value);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['parent_id'])) {
            if ($filters['parent_id'] === 'root') {
                $query->whereNull('parent_id');
            } elseif ($filters['parent_id']) {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('locations.status', $filters['status']);
        }

        $sortDir = in_array($filters['dir'] ?? '', ['asc', 'desc']) ? $filters['dir'] : 'desc';

        if (in_array($filters['sort'] ?? '', ['code', 'name'])) {
            $query->orderBy('locations.' . $filters['sort'], $sortDir);
        } else {
            $query->orderByDesc('locations.created_at');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function totalCount(): int
    {
        return Location::count();
    }

    public function activeCount(): int
    {
        return Location::where('status', ActiveStatus::Active->value)->count();
    }

    public function internalCount(): int
    {
        return Location::where('type', LocationType::Internal->value)->count();
    }

    public function getParentOptions(): Collection
    {
        $virtualRoot = Location::where('type', LocationType::Virtual->value)
            ->where('status', ActiveStatus::Active->value)
            ->first();

        $all = Location::where('type', LocationType::Internal->value)
            ->where('status', ActiveStatus::Active->value)
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        $ordered = collect();
        $visit = function ($parentId, $depth) use (&$visit, $all, &$ordered) {
            $children = $all->filter(fn ($loc) => (string) $loc->parent_id === (string) $parentId);

            foreach ($children as $node) {
                $node->depth = $depth;
                $ordered->push($node);
                $visit($node->id, $depth + 1);
            }
        };

        // Bắt đầu đệ quy từ id của vị trí ảo gốc (nếu có), thay vì null
        $visit($virtualRoot?->id, 0);

        if ($virtualRoot) {
            $virtualRoot->depth = 0;
            $ordered->prepend($virtualRoot);
        }

        return new Collection($ordered->all());
    }

    public function getActiveWarehouses(): Collection
    {
        return Warehouse::where('status', ActiveStatus::Active->value)
            ->orderBy('name')
            ->get();
    }

    public function allForTree(): Collection
    {
        return Location::with('children')
            ->orderBy('type')
            ->orderBy('code')
            ->get();
    }

    public function create(array $data): Location
    {
        return Location::create($data);
    }

    public function update(Location $location, array $data): bool
    {
        return $location->update($data);
    }

    public function delete(Location $location): bool
    {
        return $location->delete();
    }

    public function hasChildren(Location $location): bool
    {
        return $location->children()->exists();
    }

    public function hasStock(Location $location): bool
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('stocks')) {
            return false;
        }

        return $location->stocks()->where('quantity', '>', 0)->exists();
    }

    public function isRootLocation(Location $location): bool
    {
        return $location->warehouseAsRoot()->exists();
    }

    public function getDescendantIds(Location $location): array
    {
        $ids      = [];
        $children = $location->children()->select('id')->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $childFull = Location::with('children')->find($child->id);
            if ($childFull) {
                $ids = array_merge($ids, $this->getDescendantIds($childFull));
            }
        }

        return $ids;
    }
}
