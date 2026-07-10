<?php

namespace App\Http\Requests\StockRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockOutRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate::authorize() đã xử lý ở Controller, không lặp lại ở đây.
    }

    public function rules(): array
    {
        $isUpdate = $this->route('stock_out_request') !== null;

        return [
            'code' => $isUpdate
                ? [
                    'nullable', 'string', 'max:50',
                    Rule::unique('stock_out_request', 'code')->ignore($this->route('stock_out_request')),
                ]
                : 'nullable|string|max:50|unique:stock_out_request,code',
            'note' => 'nullable|string|max:500',

            // ── Danh sách vật tư yêu cầu xuất (mỗi dòng = 1 detail) ──
            'details'                    => 'required|array|min:1',
            'details.*.issue_date'       => 'nullable|date',
            'details.*.product_code'     => 'required|string|max:50',
            'details.*.product_name'     => 'required|string|max:255',
            'details.*.quantity'         => 'required|numeric|min:0.001',
            'details.*.uom_name'         => 'required|string|max:50',
            'details.*.actual_qty'       => 'nullable|numeric|min:0',
            'details.*.receiver_id'      => 'nullable|exists:employees,id',
            'details.*.sn_code'          => 'nullable|string|max:50',
            'details.*.lot_number'       => 'nullable|integer|min:1',
            'details.*.serial_number'    => 'nullable|string|max:100',
            'details.*.note'             => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'                     => 'Mã phiếu đã tồn tại.',
            'details.required'                => 'Phiếu yêu cầu xuất kho phải có ít nhất một vật tư.',
            'details.*.product_code.required' => 'Vui lòng nhập mã vật tư.',
            'details.*.product_name.required' => 'Vui lòng nhập tên vật tư.',
            'details.*.quantity.required'     => 'Vui lòng nhập số lượng.',
            'details.*.quantity.min'          => 'Số lượng phải lớn hơn 0.',
            'details.*.uom_name.required'     => 'Vui lòng nhập đơn vị tính.',
        ];
    }
}