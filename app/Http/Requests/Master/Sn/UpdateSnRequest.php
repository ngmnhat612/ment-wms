<?php

namespace App\Http\Requests\Master\Sn;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $snId = $this->route('sn')?->id;

        return [
            'name'   => "required|string|max:200",
            'code'   => "nullable|string|max:50|regex:/^[A-Za-z0-9]+$/|unique:sns,code,{$snId}",
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên dự án.',
            'code.unique'   => 'Mã dự án đã tồn tại.',
            'code.regex'    => 'Mã dự án chỉ được phép chứa chữ cái và số, không chứa ký tự đặc biệt.', 
        ];
    }
}
