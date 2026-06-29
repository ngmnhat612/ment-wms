<?php

namespace App\Services;

use App\Models\Uom;
use App\Repositories\Contracts\UomRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UomService
{
    public function __construct(
        private readonly UomRepositoryInterface $uomRepository,
        private readonly CodeGeneratorService   $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->uomRepository->search($filters);
    }

    public function totalCount(): int
    {
        return $this->uomRepository->totalCount();
    }

    public function activeCount(): int
    {
        return $this->uomRepository->activeCount();
    }

    /**
     * Lấy đơn vị tính active — dùng cho dropdown trong các module khác (Product…).
     */
    public function getActive(): Collection
    {
        return $this->uomRepository->allActive();
    }

    // ===== WRITE =====

    /**
     * Tạo mới đơn vị tính.
     * Nếu không nhập mã thì tự động sinh: DVT0001, DVT0002, …
     */
    public function create(array $data): Uom
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('uoms', 'code', 'DVT', 4);

        return $this->uomRepository->create([
            'code'   => $code,
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);
    }

    /**
     * Cập nhật đơn vị tính.
     */
    public function update(Uom $uom, array $data): Uom
    {
        $this->uomRepository->update($uom, [
            'code'   => strtoupper(trim($data['code'])),
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);

        return $uom->fresh();
    }

    /**
     * Xóa mềm đơn vị tính.
     *
     * @throws \RuntimeException khi đã được gán cho vật tư.
     */
    public function delete(Uom $uom): void
    {
        if ($this->uomRepository->hasProducts($uom)) {
            throw new \RuntimeException(
                "Không thể xóa \"{$uom->name}\" vì đã được gán cho vật tư."
            );
        }

        $this->uomRepository->delete($uom);
    }
}