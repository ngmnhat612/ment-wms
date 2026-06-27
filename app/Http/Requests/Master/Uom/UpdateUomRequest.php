<?php

namespace App\Http\Requests\Master\Uom;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $uomId = $this->route('uom')?->id;

        return [
            'name'   => "required|string|max:50",
            'code'   => "required|string|max:20|unique:uoms,code,{$uomId}",
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Vui lòng nhập tên đơn vị tính.',
            'name.max'        => 'Tên đơn vị tính không quá 50 ký tự.',
            'code.required'   => 'Vui lòng nhập mã đơn vị tính.',
            'code.unique'     => 'Mã đơn vị tính đã tồn tại.',
            'status.required' => 'Vui lòng chọn trạng thái.',
        ];
    }
}
