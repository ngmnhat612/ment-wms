<?php

namespace App\Http\Requests\Stocktake;

use App\Enums\InventoryCheckScope;
use App\Enums\InventoryCheckType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate::authorize() đã xử lý ở Controller, không lặp lại ở đây.
    }

    public function rules(): array
    {
        return [
            'warehouse_id'     => ['required', 'integer', 'exists:warehouses,id'],
            'check_scope'      => ['required', Rule::enum(InventoryCheckScope::class)],
            'check_type'       => ['required', Rule::enum(InventoryCheckType::class)],
            'check_date'       => ['required', 'date'],
            'purpose'          => ['nullable', 'string', 'max:200'],
            'note'             => ['nullable', 'string', 'max:500'],
            'location_ids'     => ['required_if:check_scope,' . InventoryCheckScope::ByArea->value, 'array'],
            'location_ids.*'   => ['integer', 'exists:locations,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'kho',
            'check_scope'  => 'phạm vi kiểm kê',
            'check_type'   => 'loại kiểm kê',
            'check_date'   => 'ngày kiểm kê',
            'location_ids' => 'khu vực / vị trí',
        ];
    }

    public function messages(): array
    {
        return [
            'location_ids.required_if' => 'Vui lòng chọn ít nhất một khu vực / vị trí khi kiểm kê theo khu vực.',
        ];
    }
}
