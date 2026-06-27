<?php

namespace App\Http\Requests\Master\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'code'      => "required|string|max:50|unique:categories,code,{$categoryId}",
            'name'      => 'required|string|max:200',
            'parent_id' => 'nullable|exists:categories,id',
            'note'      => 'nullable|string|max:500',
            'status'    => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'    => 'Vui lòng nhập mã danh mục.',
            'code.unique'      => 'Mã danh mục đã tồn tại.',
            'code.max'         => 'Mã danh mục không quá 50 ký tự.',
            'name.required'    => 'Vui lòng nhập tên danh mục.',
            'name.max'         => 'Tên danh mục không quá 200 ký tự.',
            'parent_id.exists' => 'Danh mục cha không hợp lệ.',
        ];
    }
}
