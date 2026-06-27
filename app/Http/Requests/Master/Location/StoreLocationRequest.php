<?php

namespace App\Http\Requests\Master\Location;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'         => 'required|string|max:50|unique:locations,code',
            'name'         => 'required|string|max:100',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'type'         => 'required|in:1,2',
            'parent_id'    => 'nullable|exists:locations,id',
            'status'       => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'         => 'Vui lòng nhập mã vị trí.',
            'code.unique'           => 'Mã vị trí đã tồn tại.',
            'name.required'         => 'Vui lòng nhập tên vị trí.',
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'type.required'         => 'Vui lòng chọn loại vị trí.',
        ];
    }
}
