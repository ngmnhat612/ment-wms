<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
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

            if ((int) $data['status'] === ActiveStatus::Inactive->value && $employee->account) {
                $this->accountService->deactivate($employee->account);
            }
        });

        return $employee->fresh();
    }

    /**
     * Xóa mềm nhân viên. Nếu có tài khoản đăng nhập, tài khoản cũng
     * sẽ được xóa mềm theo (cascade).
     */
    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            if ($employee->account) {
                $this->accountService->delete($employee->account);
            }

            $this->employeeRepository->delete($employee);
        });
    }
}
