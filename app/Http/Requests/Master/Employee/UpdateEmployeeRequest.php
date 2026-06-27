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
        $employeeId = $this->route('employee')?->id;

        return [
            'code'         => "required|string|max:20|unique:employees,code,{$employeeId}",
            'name'         => 'required|string|max:200',
            'phone_number' => 'nullable|string|max:20',
            'department'   => 'nullable|string|max:100',
            'status'       => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã nhân viên.',
            'code.unique'   => 'Mã nhân viên đã tồn tại.',
            'name.required' => 'Vui lòng nhập họ tên.',
        ];
    }
}
