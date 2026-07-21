<?php

namespace App\Http\Requests\Master\Department;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name'   => "required|string|max:200",
            'code'   => "nullable|string|max:20|regex:/^[A-Za-z0-9]+$/|unique:departments,code,{$departmentId}",
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Vui lòng nhập tên bộ phận.',
            'name.max'        => 'Tên bộ phận không quá 200 ký tự.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}
