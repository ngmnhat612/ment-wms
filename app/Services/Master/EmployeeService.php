<?php

namespace App\Services\Master;

use App\Enums\ActiveStatus;
use App\Models\Master\Employee;
use App\Repositories\Contracts\Master\EmployeeRepositoryInterface;
use App\Services\Concerns\ChecksForeignKeyUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\Concerns\CodeGeneratorService;

class EmployeeService
{
    use ChecksForeignKeyUsage;

    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly AccountService               $accountService,
        private readonly CodeGeneratorService          $codeGeneratorService,
    ) {}

    // ===== READ =====

    public function search(array $filters): LengthAwarePaginator
    {
        return $this->employeeRepository->search($filters);
    }

    /**
     * Lấy nhân viên active — dùng cho dropdown trong các module khác.
     */
    public function getActive(): Collection
    {
        return $this->employeeRepository->allActive();
    }

    public function totalCount(): int
    {
        return $this->employeeRepository->countAll();
    }

    public function activeCount(): int
    {
        return $this->employeeRepository->countActive();
    }

    public function accountCount(): int
    {
        return $this->employeeRepository->countWithAccount();
    }

    // ===== WRITE =====

    /**
     * Tạo mới nhân viên.
     */
    public function create(array $data): Employee
    {
        $code = !empty($data['code'])
            ? strtoupper(trim($data['code']))
            : $this->codeGeneratorService->generateCode('employees', 'code', 'NV', 4);

        return $this->employeeRepository->create([
            'code'          => $code,
            'name'          => $data['name'],
            'unique_name'   => $data['name'] . ' ' . $code,
            'phone_number'  => $data['phone_number'] ?? null,
            'department_id' => $data['department_id'] ?: null,
            'note'          => $data['note'] ?? null,
            'status'        => $data['status'],
        ]);
    }

    /**
     * Cập nhật nhân viên.
     * Khi nhân viên bị chuyển sang "Ngưng hoạt động", tài khoản đăng nhập
     * (nếu có) cũng tự động chuyển sang "Ngưng hoạt động" theo (chỉ 1 chiều).
     * Bật lại nhân viên KHÔNG tự bật lại tài khoản — phải bật thủ công.
     */
    public function update(Employee $employee, array $data): Employee
    {
        DB::transaction(function () use ($employee, $data) {
            $this->employeeRepository->update($employee, [
                'name'          => $data['name'],
                'unique_name'   => $data['name'] . ' ' . $employee->code,
                'phone_number'  => $data['phone_number'] ?? null,
                'department_id' => $data['department_id'] ?: null,
                'note'          => $data['note'] ?? null,
                'status'        => $data['status'],
            ]);

            $becomingInactive = (int) $data['status'] === ActiveStatus::Inactive->value;

            if ($becomingInactive && $employee->account && ! $employee->account->is_protected) {
                $this->accountService->deactivate($employee->account);
            }
        });

        return $employee->fresh();
    }

    /**
     * Xóa cứng nhân viên. Nếu có tài khoản đăng nhập, tài khoản cũng
     * sẽ được xóa cứng theo (cascade trong transaction).
     *
     * @throws \RuntimeException nếu tài khoản gắn với nhân viên là is_protected,
     *         hoặc nhân viên đang được tham chiếu bởi bất kỳ bảng nào khác
     *         (ví dụ là người tạo phiếu, quản lý kho, QC... — tự động phát hiện
     *         qua khóa ngoại). Trong phần lớn trường hợp thực tế, nhân viên đã
     *         từng thao tác nghiệp vụ sẽ luôn bị chặn xóa — dùng "Ngưng hoạt động"
     *         thay vì xóa cho các trường hợp đó.
     */
    public function delete(Employee $employee): void
    {
        if ($employee->account?->is_protected) {
            throw new \RuntimeException(
                "Không thể xoá nhân viên \"{$employee->name}\" vì tài khoản được bảo vệ."
            );
        }

        DB::transaction(function () use ($employee) {
            // Xóa Account TRƯỚC khi kiểm tra guardNotInUse — nếu kiểm tra trước,
            // accounts.employee_id còn tồn tại sẽ luôn chặn nhầm việc xóa
            // Employee có tài khoản đăng nhập (trường hợp hợp lệ, không phải lỗi).
            if ($employee->account) {
                $this->accountService->delete($employee->account);
            }

            $this->guardNotInUse('employees', 'id', $employee->id, 'Nhân viên', $employee->name);

            $this->employeeRepository->delete($employee);
        });
    }

    /**
     * Kiểm tra mã nhân viên đã tồn tại chưa — dùng cho validate AJAX (blur) ở
     * form Thêm/Sửa nhân viên. Chuẩn hoá code giống lúc lưu (create()) để so
     * sánh nhất quán (uppercase, trim).
     */
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return $this->employeeRepository->codeExists(
            strtoupper(trim($code)),
            $excludeId
        );
    }
}
