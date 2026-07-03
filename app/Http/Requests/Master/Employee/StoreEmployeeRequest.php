<?php

namespace App\Http\Requests\Master\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'          => 'nullable|string|max:20|unique:employees,code',
            'name'          => 'required|string|max:200',
            'phone_number'  => 'nullable|string|max:20',
            'department_id' => 'required|integer|exists:departments,id',
            'note'          => 'nullable|string|max:500',
            'status'        => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'            => 'Mã nhân viên đã tồn tại.',
            'name.required'          => 'Vui lòng nhập họ tên.',
            'department_id.required' => 'Vui lòng chọn bộ phận.',
            'department_id.exists'   => 'Bộ phận không hợp lệ.',
        ];
    }
}