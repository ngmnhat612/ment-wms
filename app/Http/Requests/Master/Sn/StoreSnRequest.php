<?php

namespace App\Http\Requests\Master\Sn;

use Illuminate\Foundation\Http\FormRequest;

class StoreSnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'   => 'required|string|max:200',
            'code'   => 'nullable|string|max:50|unique:sns,code',
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên dự án.',
            'code.unique'   => 'Mã dự án đã tồn tại.',
        ];
    }
}
