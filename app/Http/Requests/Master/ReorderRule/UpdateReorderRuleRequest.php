<?php

namespace App\Http\Requests\Master\ReorderRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReorderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ruleId = $this->route('reorder_rule')?->id;

        return [
            'product_id'   => [
                'required',
                'exists:products,id',
                Rule::unique('reorder_rules')
                    ->where('warehouse_id', $this->input('warehouse_id'))
                    ->ignore($ruleId),
            ],
            'warehouse_id' => 'required|exists:warehouses,id',
            'employee_id'  => 'required|exists:employees,id',
            'min_qty'      => 'required|numeric|min:0|max:9999',
            'max_qty'      => 'required|numeric|gte:min_qty|max:9999',
            'note'         => 'nullable|string|max:500',
            'status'       => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'   => 'Vui lòng chọn vật tư.',
            'product_id.unique'     => 'Vật tư này đã được gán quy tắc.',
            'product_id.exists'     => 'Vật tư không tồn tại trong hệ thống.',
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'employee_id.required'  => 'Vui lòng chọn người phụ trách.',
            'employee_id.exists'    => 'Người phụ trách không hợp lệ.',
            'min_qty.required'      => 'Vui lòng nhập ngưỡng tồn tối thiểu.',
            'min_qty.max'           => 'Ngưỡng tối thiểu phải < 9999.',
            'max_qty.required'      => 'Vui lòng nhập ngưỡng tồn tối đa.',
            'max_qty.gte'           => 'Ngưỡng tối đa phải ≥ ngưỡng tối thiểu.',
            'max_qty.max'           => 'Ngưỡng tối đa phải < 9999.',
        ];
    }
}
