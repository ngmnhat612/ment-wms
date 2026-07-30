<?php

namespace App\Repositories\Eloquent\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Uom;
use App\Repositories\Contracts\Master\UomRepositoryInterface;
use App\Services\Concerns\CodeGeneratorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UomRepository implements UomRepositoryInterface
{
    public function __construct(
        private readonly CodeGeneratorService $codeGeneratorService,
    ) {}

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

    public function allOrdered(): Collection
    {
        return Uom::orderBy('name')->get();
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

    public function hasConversions(Uom $uom): bool
    {
        return $uom->conversionsFrom()->exists()
            || $uom->conversionsTo()->exists();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return Uom::where('code', $code)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Khớp tên KHÔNG phân biệt hoa/thường + đã trim, giống cách người dùng
     * gõ tự do vào ô ĐVT (datalist chỉ gợi ý, không ép chọn đúng chữ hoa/thường).
     * Nếu chưa có -> tạo mới, mã tự sinh theo cùng quy tắc với UomService::create()
     * (prefix 'DVT', 4 chữ số) để nhất quán với ĐVT tạo qua màn hình master/uom.
     */
    public function findOrCreateByName(string $name): Uom
    {
        $name = trim($name);

        $existing = Uom::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return $existing;
        }

        return Uom::create([
            'code'   => $this->codeGeneratorService->generateCode('uoms', 'code', 'DVT', 4),
            'name'   => $name,
            'note'   => null,
            'status' => ActiveStatus::Active->value,
        ]);
    }
}
