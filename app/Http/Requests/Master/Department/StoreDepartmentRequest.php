<?php

namespace App\Http\Requests\Master\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:200',
            'code'   => 'nullable|string|max:50|regex:/^[A-Za-z0-9]+$/|unique:departments,code', //UPDATE
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên bộ phận.',
            'code.unique'   => 'Mã bộ phận đã tồn tại.',
            'code.regex'    => 'Mã bộ phận chỉ được phép chứa chữ cái và số, không chứa ký tự đặc biệt.', // UPDATE
        ];
    }
}
