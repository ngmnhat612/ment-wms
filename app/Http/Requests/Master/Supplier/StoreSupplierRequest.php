<?php

namespace App\Http\Requests\Master\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:200',
            'tax_code' => 'nullable|string|max:20|unique:suppliers,tax_code',
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:200',
            'address'  => 'nullable|string|max:500',
            'status'   => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => 'Vui lòng nhập tên nhà cung cấp.',
            'tax_code.unique' => 'Mã số thuế đã tồn tại.',
            'email.email'     => 'Email không hợp lệ.',
        ];
    }
}
