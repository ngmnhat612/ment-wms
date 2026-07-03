<?php

namespace App\Http\Requests\Master\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:200',
            'phone_number'  => 'nullable|string|max:20|regex:/^[A-Za-z0-9]+$/', //UPDATE
            'department_id' => 'required|integer|exists:departments,id',
            'note'          => 'nullable|string|max:500',
            'status'        => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'Vui lòng nhập họ tên.',
            'phone_number.regex'      => 'Số điện thoại không hợp lệ.', //UPDATE
            'department_id.required'  => 'Vui lòng chọn bộ phận.',
            'department_id.exists'    => 'Bộ phận không hợp lệ.',
        ];
    }
}
