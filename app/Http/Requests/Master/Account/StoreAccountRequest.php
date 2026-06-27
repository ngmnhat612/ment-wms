<?php

namespace App\Http\Requests\Master\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id|unique:accounts,employee_id',
            'username'    => 'required|string|max:50|unique:accounts,username',
            'password'    => ['required', 'confirmed', Password::min(8)],
            'role'        => 'required|in:1,2,3,4',
            'status'      => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Vui lòng chọn nhân viên.',
            'employee_id.unique'   => 'Nhân viên này đã có tài khoản.',
            'username.required'    => 'Vui lòng nhập tên đăng nhập.',
            'username.unique'      => 'Tên đăng nhập đã tồn tại.',
            'password.required'    => 'Vui lòng nhập mật khẩu.',
            'password.confirmed'   => 'Xác nhận mật khẩu không khớp.',
            'role.required'        => 'Vui lòng chọn vai trò.',
        ];
    }
}
