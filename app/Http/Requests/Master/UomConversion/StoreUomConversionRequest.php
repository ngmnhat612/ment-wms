<?php

namespace App\Http\Requests\Master\UomConversion;

use App\Models\UomConversion;
use Illuminate\Foundation\Http\FormRequest;

class StoreUomConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_uom_id' => 'required|exists:uoms,id',
            'to_uom_id'   => [
                'required',
                'exists:uoms,id',
                'different:from_uom_id',
                function ($attr, $value, $fail) {
                    $exists = UomConversion::where('from_uom_id', $this->from_uom_id)
                        ->where('to_uom_id', $value)
                        ->exists();
                    if ($exists) {
                        $fail('Quy đổi giữa hai đơn vị này đã tồn tại.');
                    }
                },
            ],
            'factor' => 'required|numeric|min:0.000001',
            'status' => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'from_uom_id.required' => 'Vui lòng chọn đơn vị nguồn.',
            'to_uom_id.required'   => 'Vui lòng chọn đơn vị đích.',
            'to_uom_id.different'  => 'Đơn vị nguồn và đích không được giống nhau.',
            'factor.required'      => 'Vui lòng nhập hệ số quy đổi.',
            'factor.min'           => 'Hệ số phải lớn hơn 0.',
        ];
    }
}
