<?php

namespace App\Http\Requests\Master\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

use Illuminate\Contracts\Validation\Validator;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'    => 'required|exists:employees,id|unique:accounts,employee_id',
            'username'       => ['required', 'string', 'max:100', 'unique:accounts,username', 'regex:/^[a-z0-9@_.]+$/'],
            'password'       => ['required', 'confirmed', 'max:500', Password::min(8)],
            'role'           => 'required|string|exists:roles,name',
            'account_status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required'     => 'Vui lòng chọn nhân viên.',
            'employee_id.unique'       => 'Nhân viên này đã có tài khoản.',
            'username.required'        => 'Vui lòng nhập tên đăng nhập.',
            'username.unique'          => 'Tên đăng nhập đã tồn tại.',
            'username.regex'           => 'Tên đăng nhập chỉ được chứa chữ thường, số và các ký tự @ _ .',
            'password.required'        => 'Vui lòng nhập mật khẩu.',
            'password.confirmed'       => 'Xác nhận mật khẩu không khớp.',
            'password.min'             => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'role.required'            => 'Vui lòng chọn vai trò.',
            'role.exists'              => 'Vai trò không hợp lệ.',
            'account_status.required'  => 'Vui lòng chọn trạng thái.',
        ];
    }
}
