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
            'role'         => 'required|in:1,2,3,4',
            'new_password' => ['nullable', 'confirmed', Password::min(8)],
            'status'       => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required'            => 'Vui lòng chọn vai trò.',
            'new_password.confirmed'   => 'Xác nhận mật khẩu không khớp.',
        ];
    }
}
