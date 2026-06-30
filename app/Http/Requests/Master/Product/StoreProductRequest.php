<?php

namespace App\Http\Requests\Master\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Product;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('products', 'code')->whereNull('deleted_at'),
            ],
            'name'                => 'required|string|max:200',
            'category_id'         => 'required|exists:categories,id',
            'uom_id'              => 'required|exists:uoms,id',
            'specification'       => 'nullable|string|max:500',
            'alert_before_expiry' => Rule::when(
                fn() => (int) $this->input('stock_rotation') === 2, // FEFO = 2
                ['required', 'integer', 'min:1'],
                ['nullable', 'integer', 'min:1']
            ),
            'tracking_type'       => 'required|in:1,2',
            'stock_rotation'      => 'required|in:1,2,3',
            'status'              => 'required|in:0,1',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'code.max'                        => 'Mã MenT không được vượt quá 50 ký tự.',
            'code.unique'                     => 'Mã MenT đã tồn tại.',
            'name.required'                   => 'Vui lòng nhập tên vật tư.',
            'name.max'                        => 'Tên vật tư không được vượt quá 200 ký tự.',
            'category_id.required'            => 'Vui lòng chọn danh mục vật tư.',
            'category_id.exists'              => 'Danh mục vật tư không hợp lệ.',
            'uom_id.required'                 => 'Vui lòng chọn đơn vị tính.',
            'uom_id.exists'                   => 'Đơn vị tính không hợp lệ.',
            'specification.max'               => 'Thông số kỹ thuật không được vượt quá 500 ký tự.',
            'alert_before_expiry.required'    => 'Vui lòng nhập số ngày cảnh báo trước hết hạn khi dùng FEFO.',
            'alert_before_expiry.integer'     => 'Số ngày cảnh báo phải là số nguyên.',
            'alert_before_expiry.min'         => 'Số ngày cảnh báo phải >= 1.',
            'tracking_type.required'          => 'Vui lòng chọn kiểu theo dõi lô/serial.',
            'tracking_type.in'                => 'Kiểu theo dõi không hợp lệ.',
            'stock_rotation.required'         => 'Vui lòng chọn phương thức xoay vòng tồn kho.',
            'stock_rotation.in'               => 'Phương thức xoay vòng không hợp lệ.',
            'status.required'                 => 'Vui lòng chọn trạng thái.',
            'status.in'                       => 'Trạng thái không hợp lệ.',
            'image.image'                     => 'File không phải là hình ảnh hợp lệ.',
            'image.mimes'                     => 'Hình ảnh phải có định dạng jpeg, png, jpg hoặc webp.',
            'image.max'                       => 'Hình ảnh không được vượt quá 2MB.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with('product_form_action', 'store') // báo blade mở lại form thường
        );
    }
}