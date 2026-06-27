<?php

namespace App\Http\Controllers\Master;

use App\Enums\AccountRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Account\StoreAccountRequest;
use App\Http\Requests\Master\Account\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    /**
     * Tạo tài khoản mới cho nhân viên chưa có account
     */
    public function store(StoreAccountRequest $request)
    {
        $this->authorize('create', Account::class);

        $employee = Employee::findOrFail($request->employee_id);

        if ($employee->account()->exists()) {
            return redirect()->route('master.employee.index')
                ->with('error', 'Nhân viên này đã có tài khoản.');
        }

        Account::create([
            'employee_id'   => $employee->id,
            'username'      => $request->username,
            'password'      => Hash::make($request->password),
            'status'        => $request->status,
        ]);

        return redirect()->route('master.employee.index')
            ->with('success', "Đã tạo tài khoản cho nhân viên \"{$employee->name}\" thành công.");
    }

    /**
     * Đổi role hoặc reset mật khẩu
     */
    public function update(UpdateAccountRequest $request, Account $account)
    {
        $this->authorize('update', $account);

        DB::transaction(function () use ($request, $account) {
            $account->status = $request->status;

            if ($request->filled('new_password')) {
                $account->password = Hash::make($request->new_password);
            }

            $account->save();
        });

        return redirect()->route('master.employee.index')
            ->with('success', "Đã cập nhật tài khoản của \"{$account->employee->name}\" thành công.");
    }

    /**
     * Xóa tài khoản (giữ lại hồ sơ nhân viên)
     */
    public function destroy(Account $account)
    {
        $this->authorize('delete', $account);

        $name = $account->employee->name;
        $account->delete();

        return redirect()->route('master.employee.index')
            ->with('success', "Đã xóa tài khoản của \"{$name}\". Hồ sơ nhân viên vẫn được giữ lại.");
    }
}
