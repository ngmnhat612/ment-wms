<?php

namespace App\Services\Master;

use App\Models\Master\Uom;
use App\Repositories\Contracts\Master\UomRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Concerns\CodeGeneratorService;

class UomService
{
    use ChecksForeignKeyUsage;

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

    /**
     * Lấy TẤT CẢ đơn vị tính (kể cả Ngừng hoạt động) — dùng cho dropdown chỉnh
     * sửa Product.
     */
    public function getAllIncludingInactive(): Collection
    {
        return $this->uomRepository->allOrdered();
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
     * Xóa cứng đơn vị tính.
     *
     * @throws \RuntimeException khi đã được gán cho vật tư, hoặc đang được
     *         tham chiếu bởi bất kỳ bảng nào khác (tự động phát hiện qua khóa ngoại).
    */
    public function delete(Uom $uom): void
    {
        $this->guardNotInUse('uoms', 'id', $uom->id, 'Đơn vị tính', $uom->name);

        $this->uomRepository->delete($uom);
    }

    /**
     * Kiểm tra mã DVT đã tồn tại chưa — dùng cho validate AJAX (blur) ở
     * form Thêm DVT. Chuẩn hoá code giống lúc lưu (create()) để so
     * sánh nhất quán (uppercase, trim).
    */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->uomRepository->codeExists(
            strtoupper(trim($code)),
            $excludeId
        );
    }
}
