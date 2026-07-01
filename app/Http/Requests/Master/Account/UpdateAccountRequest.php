<?php

namespace App\Http\Requests\Master\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role'           => 'required|string|exists:roles,name',
            'new_password'   => ['nullable', 'confirmed', Password::min(8)],
            'account_status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required'           => 'Vui lòng chọn vai trò.',
            'role.exists'             => 'Vai trò không hợp lệ.',
            'new_password.confirmed'  => 'Xác nhận mật khẩu không khớp.',
            'new_password.min'        => 'Mật khẩu mới phải có ít nhất 8 ký tự.'
        ];
    }
}