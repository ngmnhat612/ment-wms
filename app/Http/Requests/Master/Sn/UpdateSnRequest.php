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
            'code'   => "nullable|string|max:20|regex:/^[A-Za-z0-9]+$/|unique:sns,code,{$snId}",
            'note'   => 'nullable|string|max:500',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Vui lòng nhập tên dự án.',
            'name.max'        => 'Tên dự án không được vượt quá 200 ký tự.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}
