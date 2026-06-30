<?php

namespace App\Http\Requests\Master\PutawayRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePutawayRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ruleId = $this->route('putaway_rule')?->id;

        return [
            'product_id'  => [
                'nullable',
                'required_if:apply_on,product',
                'exists:products,id',
                Rule::unique('putaway_rules')
                    ->where('warehouse_id', $this->input('warehouse_id'))
                    ->ignore($ruleId),
            ],
            'category_id' => [
                'nullable',
                'required_if:apply_on,category',
                'exists:categories,id',
                Rule::unique('putaway_rules')
                    ->where('warehouse_id', $this->input('warehouse_id'))
                    ->ignore($ruleId),
            ],
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id'  => 'required|exists:locations,id',
            'note'         => 'nullable|string|max:500',
            'status'       => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required_if'     => 'Vui lòng chọn vật tư.',
            'product_id.exists'          => 'Vật tư không tồn tại trong hệ thống.',
            'product_id.unique'          => 'Vật tư này đã được gán quy tắc.',
            'category_id.required_if'    => 'Vui lòng chọn danh mục.',
            'category_id.exists'         => 'Danh mục không tồn tại trong hệ thống.',
            'category_id.unique'         => 'Danh mục này đã được gán quy tắc.',
            'warehouse_id.required'      => 'Vui lòng chọn kho.',
            'location_id.required'       => 'Vui lòng chọn vị trí gợi ý.',
            'location_id.exists'         => 'Vị trí không tồn tại.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $rule = $this->route('putaway_rule');

        $applyOn = $rule->product_id ? 'product' : 'category';
        $this->merge(['apply_on' => $applyOn]);

        if ($applyOn === 'product') {
            $this->merge(['category_id' => null]);
        } else {
            $this->merge(['product_id' => null]);
        }
    }
}