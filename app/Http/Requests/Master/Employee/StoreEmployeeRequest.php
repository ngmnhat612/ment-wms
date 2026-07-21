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
            'code'          => 'nullable|string|max:20|regex:/^[A-Za-z0-9]+$/|unique:employees,code',
            'name'          => 'required|string|max:200',
            'phone_number'  => 'nullable|string|max:20|regex:/^[A-Za-z0-9]+$/',
            'department_id' => 'required|integer|exists:departments,id',
            'note'          => 'nullable|string|max:500',
            'status'        => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'            => 'Mã nhân viên đã tồn tại.',
            'code.max'               => 'Mã nhân viên không quá 20 ký tự.',
            'code.regex'             => 'Mã nhân viên chỉ được phép chứa chữ cái và số, không chứa ký tự đặc biệt.',
            'phone_number.regex'     => 'Số điện thoại không hợp lệ.',
            'name.required'          => 'Vui lòng nhập họ tên.',
            'name.max'               => 'Họ tên không quá 200 ký tự.',
            'department_id.required' => 'Vui lòng chọn bộ phận.',
            'department_id.exists'   => 'Bộ phận không hợp lệ.',
            'status.required'        => 'Vui lòng chọn trạng thái.',
        ];
    }
}
