<?php

namespace App\Http\Requests\StockRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockOutRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate::authorize() đã xử lý ở Controller.
    }

    public function rules(): array
    {
        $isUpdate = $this->route('stock_out_request') !== null;

        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => $isUpdate
                ? [
                    'nullable', 'string', 'max:50',
                    Rule::unique('stock_out_request', 'code')->ignore($this->route('stock_out_request')),
                ]
                : 'nullable|string|max:50|unique:stock_out_request,code',
            'note' => 'nullable|string|max:500',

            // ── Mỗi detail = 1 hàng UI (khai báo tự do, giống stock_in_request) ──
            'details'                    => 'required|array|min:1',
            'details.*.issue_date'       => 'nullable|date',
            'details.*.product_code'     => 'nullable|string|max:50',
            'details.*.product_name'     => 'nullable|string|max:200',
            'details.*.quantity'         => 'required|numeric|min:0.001',
            'details.*.uom_name'         => 'nullable|string|max:200',
            'details.*.actual_qty'       => 'required|numeric|min:0',
            'details.*.receiver_id'      => 'nullable|exists:employees,id',
            'details.*.sn_code'          => 'nullable|string|max:50',
            'details.*.lot_number'       => 'nullable|integer|min:1',
            'details.*.note'             => 'nullable|string|max:500',
            'details.*.serial_number'    => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required'   => 'Vui lòng chọn kho.',
            'details.required'        => 'Phiếu yêu cầu phải có ít nhất 1 dòng vật tư.',
            'details.min'             => 'Phiếu yêu cầu phải có ít nhất 1 dòng vật tư.',
            'details.*.quantity.required' => 'Vui lòng nhập số lượng yêu cầu.',
            'details.*.quantity.min'      => 'Số lượng yêu cầu phải lớn hơn 0.',
        ];
    }

    /**
     * Chuẩn hoá dữ liệu trước khi validate: nếu actual_qty (Thực xuất)
     * bị bỏ trống, tự động coi là 0 — đồng bộ cách xử lý với
     * StockIssueRequest (theo thống nhất trước đó, tránh lỗi khi rỗng).
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('details'))) {
            return;
        }

        $details = collect($this->input('details'))->map(function ($detail) {
            if (! isset($detail['actual_qty']) || $detail['actual_qty'] === '' || $detail['actual_qty'] === null) {
                $detail['actual_qty'] = 0;
            }

            return $detail;
        })->all();

        $this->merge(['details' => $details]);
    }
}