<?php

namespace App\Http\Requests\Master\Brand;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brandId = $this->route('brand')?->id;

        return [
            'name'   => "required|string|max:100",
            'code'   => "nullable|string|max:20|unique:brands,code,{$brandId}",
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên thương hiệu.',
            'code.unique'   => 'Mã thương hiệu đã tồn tại.',
        ];
    }
}
