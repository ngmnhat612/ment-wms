<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Models\Account;
use App\Models\Employee;
use App\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}

    /**
     * Tạo tài khoản đăng nhập cho một nhân viên chưa có account.
     *
     * @throws \RuntimeException khi nhân viên đã có tài khoản.
     */
    public function create(Employee $employee, array $data): Account
    {
        if ($employee->account()->exists()) {
            throw new \RuntimeException("Nhân viên \"{$employee->name}\" đã có tài khoản.");
        }

        return DB::transaction(function () use ($employee, $data) {
            $account = $this->accountRepository->create([
                'employee_id' => $employee->id,
                'username'    => $data['username'],
                'password'    => Hash::make($data['password']),
                'status'      => $data['account_status'] ?? 1,
            ]);

            $account->syncRoles([$data['role']]);

            return $account;
        });
    }

    /**
     * Cập nhật vai trò, trạng thái, hoặc reset mật khẩu của tài khoản.
     */
    public function update(Account $account, array $data): Account
    {
        DB::transaction(function () use ($account, $data) {
            $payload = ['status' => $data['account_status']];

            if (!empty($data['new_password'])) {
                $payload['password'] = Hash::make($data['new_password']);
            }

            $this->accountRepository->update($account, $payload);

            $account->syncRoles([$data['role']]);
        });

        return $account->fresh();
    }

    /**
     * Xóa tài khoản (giữ lại hồ sơ nhân viên).
     */
    public function delete(Account $account): void
    {
        $this->accountRepository->delete($account);
    }

    public function deactivate(Account $account): void
    {
        $this->accountRepository->update($account, ['status' => ActiveStatus::Inactive->value]);
    }
}
