<?php

namespace App\Services;

use App\Models\Location;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LocationService
{
    public function __construct(
        private readonly LocationRepositoryInterface $locationRepository,
        private readonly CodeGeneratorService        $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->locationRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->locationRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->locationRepository->activeCount();
    }

    public function internalCount(): int
    {
        return $this->locationRepository->internalCount();
    }

    /**
     * Lấy danh sách vị trí Internal active — dùng cho dropdown chọn cha.
     */
    public function getParentOptions(): Collection
    {
        return $this->locationRepository->getParentOptions();
    }

    /**
     * Lấy danh sách kho active — dùng cho dropdown chọn kho.
     */
    public function getActiveWarehouses(): Collection
    {
        return $this->locationRepository->getActiveWarehouses();
    }

    /**
     * Lấy toàn bộ vị trí để dựng cây.
     */
    public function getTreeRoots(): Collection
    {
        $all = $this->locationRepository->allForTree();

        return $all->whereNull('parent_id')->values();
    }

    // ===== WRITE =====

    /**
     * Tạo mới vị trí.
     *
     * @throws \RuntimeException khi Virtual có parent.
     */
    public function create(array $data): Location
    {
        $this->guardVirtualParent($data);

        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('locations', 'code', 'VT', 4);

        $type = $data['type'] ?? \App\Enums\LocationType::Internal->value;

        return $this->locationRepository->create([
            'parent_id'    => ($type == 1) ? ($data['parent_id'] ?? null) : null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'code'         => $code,
            'name'         => $data['name'],
            'type'         => $type,
            'status'       => $data['status'],
            'note'         => $data['note'] ?? null,
        ]);
    }

    /**
     * Cập nhật vị trí.
     *
     * @throws \RuntimeException khi chọn vị trí con làm cha (vòng tròn) hoặc Virtual có parent.
     */
    public function update(Location $location, array $data): Location
    {
        $type = $data['type'] ?? $location->type->value;

        $this->guardVirtualParent($data);

        if (!empty($data['parent_id'])) {
            $this->guardCircularParent($location, (int) $data['parent_id']);
        }

        $this->locationRepository->update($location, [
            'parent_id'    => ($type == 1) ? ($data['parent_id'] ?? null) : null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'code'         => strtoupper(trim($data['code'])),
            'name'         => $data['name'],
            'type'         => $type,
            'status'       => $data['status'],
            'note'         => $data['note'] ?? null,
        ]);

        return $location->fresh();
    }

    /**
     * Xóa vị trí.
     *
     * @throws \RuntimeException khi có con, tồn kho, hoặc là root của kho.
     */
    public function delete(Location $location): void
    {
        if ($this->locationRepository->hasChildren($location)) {
            throw new \RuntimeException(
                "Không thể xóa \"{$location->name}\" vì có vị trí con."
            );
        }

        if ($this->locationRepository->hasStock($location)) {
            throw new \RuntimeException(
                "Không thể xóa \"{$location->name}\" vì đang có tồn kho."
            );
        }

        if ($this->locationRepository->isRootLocation($location)) {
            throw new \RuntimeException(
                "Không thể xóa vị trí gốc của kho \"{$location->name}\"."
            );
        }

        $this->locationRepository->delete($location);
    }

    // ===== PRIVATE HELPERS =====

    /**
     * Ngăn Virtual location có parent.
     *
     * @throws \RuntimeException
     */
    private function guardVirtualParent(array $data): void
    {
        if (($data['type'] ?? null) == 2 && !empty($data['parent_id'])) {
            throw new \RuntimeException(
                'Vị trí ảo (Virtual) không thể có vị trí cha.'
            );
        }
    }

    /**
     * Ngăn chọn vị trí con / chính nó làm cha (vòng tròn).
     *
     * @throws \RuntimeException
     */
    private function guardCircularParent(Location $location, int $newParentId): void
    {
        if ($newParentId === $location->id) {
            throw new \RuntimeException(
                'Không thể chọn chính vị trí này làm vị trí cha.'
            );
        }

        $descendantIds = $this->locationRepository->getDescendantIds($location);

        if (in_array($newParentId, $descendantIds)) {
            throw new \RuntimeException(
                'Không thể chọn vị trí con làm vị trí cha.'
            );
        }
    }
}
