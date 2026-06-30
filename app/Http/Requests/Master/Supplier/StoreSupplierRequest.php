<?php

namespace App\Http\Requests\Master\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'     => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('suppliers', 'code'),
            ],
            'name'     => 'required|string|max:200',
            'tax_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:200',
            'address'  => 'nullable|string|max:500',
            'note'     => 'nullable|string|max:500',
            'status'   => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'      => 'Mã nhà cung cấp đã tồn tại.',
            'name.required'    => 'Vui lòng nhập tên nhà cung cấp.',
            'email.email'      => 'Email không hợp lệ.',
        ];
    }
}