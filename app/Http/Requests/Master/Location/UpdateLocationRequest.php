<?php

namespace App\Http\Requests\Master\Location;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $locationId = $this->route('location')?->id;

        return [
            'parent_id'    => 'nullable|exists:locations,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'code'         => "required|string|max:50|unique:locations,code,{$locationId}",
            'name'         => 'required|string|max:100',
            'type'         => 'nullable|in:1,2',
            'status'       => 'required|in:0,1',
            'note'         => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'  => 'Vui lòng nhập mã vị trí.',
            'code.unique'    => 'Mã vị trí đã tồn tại.',
            'code.max'       => 'Mã vị trí không quá 50 ký tự.',
            'name.required'  => 'Vui lòng nhập tên vị trí.',
            'name.max'       => 'Tên vị trí không quá 100 ký tự.',
        ];
    }
}
