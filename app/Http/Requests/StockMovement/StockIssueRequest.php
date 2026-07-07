<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StockIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('issue') !== null;

        return [
            'warehouse_id' => 'required|exists:warehouses,id',
            'code'         => $isUpdate
                ? 'nullable|string|max:50'
                : 'nullable|string|max:50|unique:stock_issue,code',
            'issue_date' => 'required|date',
            'note'       => 'nullable|string|max:500',

            'details'                => 'required|array|min:1',
            'details.*.product_id'  => 'required|exists:products,id',
            'details.*.uom_id'      => 'required|exists:uoms,id',
            'details.*.expected_qty' => 'required|numeric|min:0.001',
            'details.*.actual_qty'  => 'nullable|numeric|min:0',
            'details.*.location_id' => 'required|exists:locations,id',
            'details.*.lot_id'      => 'nullable|exists:lots,id',
            'details.*.serial_id'   => 'nullable|exists:serials,id',
            'details.*.sub_warehouse' => 'nullable|string|max:50',
            'details.*.receiver_id' => 'nullable|exists:employees,id',
            'details.*.sn_id'       => 'nullable|integer',
            'details.*.note'        => 'nullable|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required'          => 'Vui lòng chọn kho xuất.',
            'code.unique'                     => 'Mã phiếu đã tồn tại.',
            'issue_date.required'             => 'Vui lòng chọn ngày xuất.',
            'details.required'                => 'Phiếu xuất phải có ít nhất một hàng hóa.',
            'details.*.product_id.required'   => 'Vui lòng chọn hàng hóa.',
            'details.*.uom_id.required'       => 'Vui lòng chọn đơn vị tính.',
            'details.*.expected_qty.required' => 'Vui lòng nhập số lượng dự kiến.',
            'details.*.expected_qty.min'      => 'Số lượng dự kiến phải lớn hơn 0.',
            'details.*.location_id.required'  => 'Vui lòng chọn vị trí kho.',
        ];
    }

    /**
     * Chặn trùng serial_id trong cùng 1 phiếu — mỗi serial chỉ xuất được 1 lần.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $serials = collect($this->input('details', []))
                ->pluck('serial_id')
                ->filter();

            if ($serials->count() !== $serials->unique()->count()) {
                $validator->errors()->add(
                    'details',
                    'Mỗi số Serial chỉ được xuất một lần trong cùng phiếu.'
                );
            }
        });
    }
}