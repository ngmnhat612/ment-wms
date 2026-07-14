<?php

namespace App\Http\Requests\Stocktake;

use Illuminate\Foundation\Http\FormRequest;

class StockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate::authorize() đã xử lý ở Controller, không lặp lại ở đây.
    }

    public function rules(): array
    {
        return [
            'adjustment_date' => ['required', 'date'],
            'note'            => ['nullable', 'string', 'max:500'],
            'detail_ids'      => ['required', 'array', 'min:1'],
            'detail_ids.*'    => ['integer', 'exists:inventory_check_detail,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'adjustment_date' => 'ngày điều chỉnh',
            'detail_ids'      => 'dòng chênh lệch',
        ];
    }

    public function messages(): array
    {
        return [
            'detail_ids.required' => 'Vui lòng chọn ít nhất một dòng chênh lệch cần điều chỉnh.',
            'detail_ids.min'      => 'Vui lòng chọn ít nhất một dòng chênh lệch cần điều chỉnh.',
        ];
    }
}
