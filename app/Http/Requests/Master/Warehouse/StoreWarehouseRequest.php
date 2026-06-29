<?php

namespace App\Http\Requests\Master\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'       => 'nullable|string|max:50|unique:warehouses,code',
            'name'       => 'required|string|max:200',
            'manager_id' => 'required|exists:employees,id',
            'phone'      => 'nullable|string|max:20',
            'address'    => 'nullable|string|max:500',
            'note'       => 'nullable|string|max:500',
            'status'     => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'      => 'Vui lòng nhập mã kho.',
            'code.unique'        => 'Mã kho đã tồn tại.',
            'name.required'      => 'Vui lòng nhập tên kho.',
            'manager_id.required'  => 'Vui lòng chọn quản lý kho.',
            'manager_id.exists'  => 'Quản lý kho không hợp lệ.',
        ];
    }
}