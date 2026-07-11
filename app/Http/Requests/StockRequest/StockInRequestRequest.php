<?php

namespace App\Http\Requests\StockRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockInRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate::authorize() đã xử lý ở Controller.
    }

    public function rules(): array
    {
        $isUpdate = $this->route('stock_in_request') !== null;

        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => $isUpdate
                ? [
                    'nullable', 'string', 'max:50',
                    Rule::unique('stock_in_request', 'code')->ignore($this->route('stock_in_request')),
                ]
                : 'nullable|string|max:50|unique:stock_in_request,code',
            'note' => 'nullable|string|max:500',

            // ── Mỗi detail = 1 hàng UI (khai báo tự do, không bắt buộc product_id
            // vì vật tư có thể chưa tồn tại trong hệ thống — xem new_product_code) ──
            'details'                          => 'required|array|min:1',
            'details.*.reference_no'           => 'nullable|string|max:100',
            'details.*.received_date'          => 'required|date',
            'details.*.product_code'           => 'nullable|string|max:50',
            'details.*.product_name'           => 'nullable|string|max:200',
            'details.*.product_specification'  => 'nullable|string|max:500',
            'details.*.brand_name'             => 'nullable|string|max:200',
            'details.*.quantity'               => 'required|numeric|min:0.001',
            'details.*.uom_name'               => 'nullable|string|max:200',
            'details.*.lot_number'             => 'nullable|integer|min:1',
            'details.*.note'                   => 'nullable|string|max:500',
            'details.*.new_product_code'       => 'nullable|string|max:50',
            'details.*.qc_date'                => 'nullable|date',
            'details.*.qc_employee_id'         => 'nullable|exists:employees,id',
            'details.*.qc_result'              => 'nullable|string|max:100',
            'details.*.rejected_qty'           => 'nullable|numeric|min:0',
            'details.*.solution'               => 'nullable|string|max:500',
            'details.*.requester_id'           => 'nullable|exists:employees,id',
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
            'details.*.received_date.required' => 'Vui lòng nhập ngày về.',
        ];
    }

    /**
     * Chuẩn hoá dữ liệu trước khi validate: nếu rejected_qty bị bỏ trống,
     * tự động coi là 0 thay vì để null (đồng bộ với cách xử lý actual_qty
     * ở StockIssueRequest/StockReceiptRequest).
     */
    protected function prepareForValidation(): void
    {
        if (! is_array($this->input('details'))) {
            return;
        }

        $details = collect($this->input('details'))->map(function ($detail) {
            if (! isset($detail['rejected_qty']) || $detail['rejected_qty'] === '') {
                $detail['rejected_qty'] = 0;
            }

            return $detail;
        })->all();

        $this->merge(['details' => $details]);
    }
}