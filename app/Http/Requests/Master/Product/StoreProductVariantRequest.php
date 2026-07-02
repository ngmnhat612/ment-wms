<?php

namespace App\Http\Requests\Master\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Product;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'parent_code' => [
                'required', 'string',
                Rule::exists('products', 'code')->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('products', 'code'),
            ],
            'name'        => 'required|string|max:200',
            'specification' => 'nullable|string|max:500',
            'status'      => 'required|in:0,1',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            // category_id, uom_id, tracking_type, stock_rotation,
            // alert_before_expiry — không validate vì kế thừa từ cha ở service
        ];
    }

    public function messages(): array
    {
        return [
            'parent_code.required' => 'Vui lòng nhập mã MenT gốc.',
            'parent_code.exists'   => 'Mã MenT gốc không tồn tại trong hệ thống.',
            'code.max'             => 'Mã biến thể không được vượt quá 50 ký tự.',
            'code.unique'          => 'Mã biến thể đã tồn tại.',
            'name.required'        => 'Vui lòng nhập tên biến thể.',
            'name.max'             => 'Tên không được vượt quá 200 ký tự.',
            'specification.max'    => 'Thông số kỹ thuật không được vượt quá 500 ký tự.',
            'status.required'      => 'Vui lòng chọn trạng thái.',
            'status.in'            => 'Trạng thái không hợp lệ.',
            'image.image'          => 'File không phải là hình ảnh hợp lệ.',
            'image.mimes'          => 'Hình ảnh phải có định dạng jpeg, png, jpg hoặc webp.',
            'image.max'            => 'Hình ảnh không được vượt quá 2MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with('product_form_action', 'variant') // báo blade mở lại form biến thể
        );
    }
}