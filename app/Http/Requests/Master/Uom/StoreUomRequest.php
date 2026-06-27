<?php

namespace App\Http\Requests\Master\Uom;

use Illuminate\Foundation\Http\FormRequest;

class StoreUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'   => 'nullable|string|max:20|unique:uoms,code',
            'name'   => 'required|string|max:50',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.max'        => 'Mã đơn vị tính không quá 20 ký tự.',
            'code.unique'     => 'Mã đơn vị tính đã tồn tại.',
            'name.required'   => 'Vui lòng nhập tên đơn vị tính.',
            'name.max'        => 'Tên đơn vị tính không quá 50 ký tự.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}