<?php

namespace App\Http\Requests\Master\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id;

        return [
            'code'    => "required|string|max:50|unique:warehouses,code,{$warehouseId}",
            'name'    => 'required|string|max:200',
            'phone'   => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status'  => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã kho.',
            'code.unique'   => 'Mã kho đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên kho.',
        ];
    }
}
