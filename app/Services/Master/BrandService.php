<?php

namespace App\Services\Master;

use App\Models\Master\Brand;
use App\Repositories\Contracts\Master\BrandRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Concerns\CodeGeneratorService;

class BrandService
{
    use ChecksForeignKeyUsage;

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
     * Xóa cứng thương hiệu.
     *
     * Trước đây hàm này xóa thẳng không kiểm tra gì — với soft delete, hậu quả
     * bị che giấu vì bản ghi vẫn còn trong DB (chỉ ẩn). Với xóa cứng, thiếu kiểm
     * tra sẽ làm vật tư đang dùng thương hiệu này mất dữ liệu tham chiếu thật.
     *
     * @throws \RuntimeException khi đang được sử dụng bởi bảng khác.
     */
    public function delete(Brand $brand): void
    {
        $this->guardNotInUse('brands', 'id', $brand->id, 'Thương hiệu', $brand->name);

        $this->brandRepository->delete($brand);
    }

    /**
     * Kiểm tra mã đã tồn tại chưa — dùng cho validate AJAX (blur) ở form Thêm/Sửa.
     * Chuẩn hoá code giống lúc lưu (uppercase, trim) để so sánh nhất quán.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->brandRepository->codeExists(
            strtoupper(trim($code)),
            $excludeId
        );
    }
}
