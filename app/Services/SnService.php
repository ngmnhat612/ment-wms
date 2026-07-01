<?php

namespace App\Services;

use App\Models\Sn;
use App\Repositories\Contracts\SnRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SnService
{
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
     * Xóa mềm dự án.
     */
    public function delete(Sn $sn): void
    {
        $this->snRepository->delete($sn);
    }
}
