<?php

namespace App\Services;

use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BrandService
{
    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository,
        private readonly CodeGeneratorService     $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->brandRepository->search($filters);
    }

    /**
     * Lấy thương hiệu active — dùng cho dropdown trong các module khác (Product…).
     */
    public function getActive(): Collection
    {
        return $this->brandRepository->allActive();
    }

    // ===== WRITE =====

    /**
     * Tạo mới thương hiệu.
     */
    public function create(array $data): Brand
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('brands', 'code', 'TH', 4);

        return $this->brandRepository->create([
            'code'   => $code,
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);
    }

    /**
     * Cập nhật thương hiệu.
     */
    public function update(Brand $brand, array $data): Brand
    {
        $this->brandRepository->update($brand, [
            'code'   => strtoupper(trim($data['code'])),
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);

        return $brand->fresh();
    }

    /**
     * Xóa mềm thương hiệu.
     *
     * @throws \RuntimeException khi có vật tư đang dùng.
     */
    public function delete(Brand $brand): void
    {
        $this->brandRepository->delete($brand);
    }
}