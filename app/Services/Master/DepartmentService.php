<?php

namespace App\Services\Master;

use App\Models\Master\Department;
use App\Repositories\Contracts\Master\DepartmentRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Concerns\CodeGeneratorService;

class DepartmentService
{
    use ChecksForeignKeyUsage;

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

    /**
     * Lấy TẤT CẢ bộ phận (kể cả Ngưng hoạt động) — dùng cho dropdown chỉnh sửa
     * Employee.
     */
    public function getAllIncludingInactive(): Collection
    {
        return $this->departmentRepository->allOrdered();
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
     * Xóa cứng bộ phận.
     *
     * @throws \RuntimeException khi đang có nhân viên thuộc bộ phận này, hoặc
     *         đang được tham chiếu bởi bất kỳ bảng nào khác.
     */
    public function delete(Department $department): void
    {
        $this->guardNotInUse('departments', 'id', $department->id, 'Bộ phận', $department->name);

        $this->departmentRepository->delete($department);
    }

    /**
     * Kiểm tra mã bộ phận đã tồn tại chưa — dùng cho validate AJAX (blur) ở
     * form Thêm bộ phận. Chuẩn hoá code giống lúc lưu (create()) để so
     * sánh nhất quán (uppercase, trim).
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->departmentRepository->codeExists(
            strtoupper(trim($code)),
            $excludeId
        );
    }
}
