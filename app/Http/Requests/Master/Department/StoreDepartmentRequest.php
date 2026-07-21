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
            'code'   => 'nullable|string|max:20|regex:/^[A-Za-z0-9]+$/|unique:departments,code',
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Vui lòng nhập tên bộ phận.',
            'name.max'        => 'Tên bộ phận không quá 200 ký tự.',
            'code.unique'     => 'Mã bộ phận đã tồn tại.',
            'code.max'        => 'Mã bộ phận không quá 20 ký tự.',
            'code.regex'      => 'Mã bộ phận chỉ được phép chứa chữ cái và số, không chứa ký tự đặc biệt.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}
