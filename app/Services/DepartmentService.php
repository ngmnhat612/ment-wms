<?php

namespace App\Services;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $departmentRepository,
        private readonly CodeGeneratorService           $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->departmentRepository->search($filters);
    }

    /**
     * Lấy bộ phận active — dùng cho dropdown trong các module khác (Employee…).
     */
    public function getActive(): Collection
    {
        return $this->departmentRepository->allActive();
    }

    // ===== WRITE =====

    /**
     * Tạo mới bộ phận.
     */
    public function create(array $data): Department
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('departments', 'code', 'BP', 4);

        return $this->departmentRepository->create([
            'code'   => $code,
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);
    }

    /**
     * Cập nhật bộ phận.
     */
    public function update(Department $department, array $data): Department
    {
        $this->departmentRepository->update($department, [
            'code'   => strtoupper(trim($data['code'])),
            'name'   => $data['name'],
            'note'   => $data['note'] ?? null,
            'status' => $data['status'],
        ]);

        return $department->fresh();
    }

    /**
     * Xóa mềm bộ phận.
     *
     * @throws \RuntimeException khi đang có nhân viên thuộc bộ phận này.
     */
    public function delete(Department $department): void
    {
        if ($department->employees()->exists()) {
            throw new \RuntimeException('Không thể xóa bộ phận đã gán cho nhân viên.');
        }

        $this->departmentRepository->delete($department);
    }
}
