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
            'min_qty'      => 'required|numeric|min:0|max:99999999',
            'max_qty'      => 'required|numeric|gte:min_qty|max:99999999',
            'note'         => 'nullable|string|max:500',
            'status'       => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'Vui lòng chọn kho.',
            'employee_id.required'  => 'Vui lòng chọn người phụ trách.',
            'employee_id.exists'    => 'Người phụ trách không hợp lệ.',
            'min_qty.required'      => 'Vui lòng nhập Min hoặc Max.',
            'min_qty.max'           => 'Ngưỡng tối thiểu phải bé hơn 99999999.',
            'max_qty.required'      => 'Vui lòng nhập Min hoặc Max.',
            'max_qty.gte'           => 'Ngưỡng tối thiểu không được vượt quá ngưỡng tối đa.',
            'max_qty.max'           => 'Ngưỡng tối đa phải bé hơn 99999999.',
            'status.required'       => 'Vui lòng chọn trạng thái.',
        ];
    }
}
