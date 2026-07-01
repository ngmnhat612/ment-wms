<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Account\StoreAccountRequest;
use App\Http\Requests\Master\Account\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Employee;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    // ===== STORE =====

    /**
     * Tạo tài khoản mới cho nhân viên chưa có account.
     */
    public function store(StoreAccountRequest $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('create', Account::class);

        try {
            $this->accountService->create($employee, $request->validated());
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('master.employee.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('master.employee.index')
            ->with('success', "Đã tạo tài khoản cho nhân viên \"{$employee->name}\" thành công.");
    }

    // ===== UPDATE =====

    /**
     * Đổi vai trò, trạng thái hoặc reset mật khẩu.
     */
    public function update(UpdateAccountRequest $request, Employee $employee): RedirectResponse
    {
        $account = $employee->account()->firstOrFail();

        Gate::authorize('update', $account);

        $this->accountService->update($account, $request->validated());

        return redirect()
            ->route('master.employee.index')
            ->with('success', "Đã cập nhật tài khoản của \"{$employee->name}\" thành công.");
    }

    // ===== DESTROY =====

    /**
     * Xóa tài khoản (giữ lại hồ sơ nhân viên).
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $account = $employee->account()->firstOrFail();

        Gate::authorize('delete', $account);

        $name = $employee->name;

        $this->accountService->delete($account);

        return redirect()
            ->route('master.employee.index')
            ->with('success', "Đã xóa tài khoản của \"{$name}\". Hồ sơ nhân viên vẫn được giữ lại.");
    }
}