<?php

namespace App\Http\Requests\Master\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Master\Product;


class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        if (!$product instanceof Product) {
            return false;
        }

        return $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        $productId = is_object($this->route('product'))
            ? $this->route('product')->id
            : $this->route('product');

        return [
            'code'                => "nullable|string|max:20|unique:products,code,{$productId}", // ignore current;
            'name'                => 'required|string|max:200',
            'category_id'         => 'required|exists:categories,id',
            // uom_id không còn validate ở đây: ĐVT giờ là ô nhập tự do
            // (datalist gợi nhớ) — xem uom_name bên dưới. Service sẽ tự
            // resolve/tạo Uom theo tên rồi gán uom_id trước khi lưu Product.
            'uom_name'            => 'required|string|max:50',
            'specification'       => 'nullable|string|max:500',
            'alert_before_expiry' => Rule::when(
                fn() => (int) $this->input('stock_rotation') === 2,
                ['required', 'integer', 'min:1'],
                ['nullable', 'integer', 'min:1']
            ),
            'tracking_type'       => 'required|in:1,2',
            'stock_rotation'      => 'required|in:1,2,3',
            'status'              => 'required|in:0,1',
            'image'               => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'min_qty'             => 'nullable|integer|min:0|max:99999999',
            'max_qty'             => 'nullable|integer|min:0|max:99999999|gte:min_qty',
            'location_id'         => 'nullable|exists:locations,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                   => 'Vui lòng nhập tên vật tư.',
            'name.max'                        => 'Tên vật tư không được vượt quá 200 ký tự.',
            'uom_name.required'               => 'Vui lòng nhập đơn vị tính.',
            'uom_name.max'                    => 'Đơn vị tính không được vượt quá 50 ký tự.',
            'specification.max'               => 'Thông số kỹ thuật không được vượt quá 500 ký tự.',
            'alert_before_expiry.required'    => 'Vui lòng nhập số ngày cảnh báo trước hết hạn khi dùng FEFO.',
            'alert_before_expiry.integer'     => 'Số ngày cảnh báo phải là số nguyên.',
            'alert_before_expiry.min'         => 'Số ngày cảnh báo phải lớn hơn hoặc bằng 1.',
            'tracking_type.required'          => 'Vui lòng chọn kiểu theo dõi lô/serial.',
            'tracking_type.in'                => 'Kiểu theo dõi không hợp lệ.',
            'stock_rotation.required'         => 'Vui lòng chọn phương thức xoay vòng tồn kho.',
            'stock_rotation.in'               => 'Phương thức xoay vòng không hợp lệ.',
            'status.required'                 => 'Vui lòng chọn trạng thái.',
            'status.in'                       => 'Trạng thái không hợp lệ.',
            'image.image'                     => 'File không phải là hình ảnh hợp lệ.',
            'image.mimes'                     => 'Hình ảnh phải có định dạng jpeg, png, jpg hoặc webp.',
            'image.max'                       => 'Hình ảnh không được vượt quá 2MB.',
            'max_qty.gte'                     => 'Ngưỡng tối thiểu không được vượt quá ngưỡng tối đa.',
            'min_qty.max'                     => 'Ngưỡng tối thiểu phải bé hơn 99999999.',
            'max_qty.max'                     => 'Ngưỡng tối đa phải bé hơn 99999999.',
            'location_id'                     => 'nullable|exists:locations,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $product   = $this->route('product');
        $productId = $product instanceof Product ? $product->id : $product;

        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withErrors($validator)
                ->with('product_form_action', 'update:' . $productId)
        );
    }
}
