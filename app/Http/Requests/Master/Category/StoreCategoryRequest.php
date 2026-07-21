<?php

namespace App\Http\Requests\Master\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'      => 'nullable|string|max:20|regex:/^[A-Za-z0-9]+$/|unique:categories,code',
            'name'      => 'required|string|max:200',
            'parent_id' => 'nullable|exists:categories,id',
            'note'      => 'nullable|string|max:500',
            'status'    => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'      => 'Mã danh mục đã tồn tại.',
            'code.max'         => 'Mã danh mục không quá 20 ký tự.',
            'code.regex'       => 'Mã danh mục chỉ được phép chứa chữ cái và số, không chứa ký tự đặc biệt.',
            'name.required'    => 'Vui lòng nhập tên danh mục.',
            'name.max'         => 'Tên danh mục không quá 200 ký tự.',
            'parent_id.exists' => 'Danh mục cha không hợp lệ.',
            'status.required'  => 'Vui lòng chọn trạng thái.',
        ];
    }
}
