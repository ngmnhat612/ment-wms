<?php

namespace App\Http\Requests\Master\ReorderRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReorderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'   => [
                'required',
                'exists:products,id',
                Rule::unique('reorder_rules')->where('warehouse_id', $this->input('warehouse_id')),
            ],
            'warehouse_id' => 'required|exists:warehouses,id',
            'employee_id'  => 'nullable|exists:employees,id',
            'min_qty'      => 'required|numeric|min:0',
            'max_qty'      => 'required|numeric|gte:min_qty',
            'status'       => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'   => 'Vui lòng chọn hàng hóa.',
            'product_id.unique'     => 'Hàng hóa này đã có reorder rule tại kho đã chọn.',
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'min_qty.required'      => 'Vui lòng nhập ngưỡng tồn tối thiểu.',
            'max_qty.required'      => 'Vui lòng nhập ngưỡng tồn tối đa.',
            'max_qty.gte'           => 'Ngưỡng tối đa phải ≥ ngưỡng tối thiểu.',
        ];
    }
}
