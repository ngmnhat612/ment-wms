<?php

namespace App\Services\Master;

use App\Models\Master\Sn;
use App\Repositories\Contracts\Master\SnRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Concerns\CodeGeneratorService;

class SnService
{
    use ChecksForeignKeyUsage;

    public function __construct(
        private readonly SnRepositoryInterface $snRepository,
        private readonly CodeGeneratorService  $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->snRepository->search($filters);
    }

    /**
     * Lấy dự án active — dùng cho dropdown trong các module khác.
     */
    public function getActive(): Collection
    {
        return $this->snRepository->allActive();
    }

    // ===== WRITE =====

    /**
     * Tạo mới dự án.
     */
    public function create(array $data): Sn
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('sns', 'code', 'DA', 4);

        return $this->snRepository->create([
            'code'   => $code,
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);
    }

    /**
     * Cập nhật dự án.
     */
    public function update(Sn $sn, array $data): Sn
    {
        $this->snRepository->update($sn, [
            'code'   => trim($data['code']),
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);

        return $sn->fresh();
    }

    /**
     * Xóa cứng dự án.
     *
     * Trước đây hàm này xóa thẳng không kiểm tra gì — vá lại tương tự Brand.
     *
     * @throws \RuntimeException khi đang được sử dụng bởi bảng khác.
     */
    public function delete(Sn $sn): void
    {
        $this->guardNotInUse('sns', 'id', $sn->id, 'Dự án', $sn->name);

        $this->snRepository->delete($sn);
    }

    /**
     * Kiểm tra mã đã tồn tại chưa — dùng cho validate AJAX (blur) ở form Thêm/Sửa.
     * Chuẩn hoá code giống lúc lưu (uppercase, trim) để so sánh nhất quán.
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->snRepository->codeExists(
            strtoupper(trim($code)),
            $excludeId
        );
    }
}
