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
            'name'   => "required|string|max:200",
            'code'   => "nullable|string|max:20|regex:/^[A-Za-z0-9]+$/|unique:brands,code,{$brandId}", //UPDATE
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Vui lòng nhập tên thương hiệu.',
            'name.max'         => 'Tên thương hiệu không quá 200 ký tự.'
            'status.required'  => 'Vui lòng chọn trạng thái.',
        ];
    }
}
