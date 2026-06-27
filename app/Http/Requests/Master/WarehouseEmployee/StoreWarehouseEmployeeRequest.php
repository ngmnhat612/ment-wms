<?php

namespace App\Http\Requests\Master\WarehouseEmployee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'employee_id'  => [
                'required',
                'exists:employees,id',
                Rule::unique('warehouse_employees')->where('warehouse_id', $this->warehouse_id),
            ],
            'is_primary'   => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'employee_id.required'  => 'Vui lòng chọn nhân viên.',
            'employee_id.unique'    => 'Nhân viên này đã được gán vào kho này.',
        ];
    }
}
