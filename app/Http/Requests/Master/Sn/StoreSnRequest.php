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
            'code'   => 'nullable|string|max:20|regex:/^[A-Za-z0-9_]+$/|unique:sns,code',
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Vui lòng nhập tên dự án.',
            'name.max'        => 'Tên dự án không được vượt quá 200 ký tự.',
            'code.max'        => 'Mã dự án không được vượt quá 20 ký tự.',
            'code.unique'     => 'Mã dự án đã tồn tại.',
            'code.regex'      => 'Mã dự án chỉ được phép chứa chữ cái, số và dấu gạch dưới (_).',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}
