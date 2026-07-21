<?php

namespace App\Http\Requests\Master\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier')?->id;

        return [
            'code'     => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('suppliers', 'code')->ignore($supplierId),
            ],
            'name'     => 'required|string|max:200',
            'tax_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'phone'    => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:100',
            'address'  => 'nullable|string|max:200',
            'note'     => 'nullable|string|max:500',
            'status'   => 'required|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Vui lòng nhập tên nhà cung cấp.',
            'name.max'         => 'Tên nhà cung cấp không được vượt quá 200 ký tự.',
            'tax_code.max'     => 'Mã số thuế không được vượt quá 20 ký tự.',
            'phone.max'        => 'Số điện thoại không được vượt quá 20 ký tự.',
            'email.email'      => 'Email không hợp lệ.',
            'email.max'        => 'Email không được vượt quá 100 ký tự.',
            'address.max'      => 'Địa chỉ không được vượt quá 200 ký tự.',
            'status.required'  => 'Vui lòng chọn trạng thái.',
        ];
    }
}
