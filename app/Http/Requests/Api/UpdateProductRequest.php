<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'variants' => ['sometimes', 'array'],
            'variants.*.id' => ['sometimes', 'exists:product_variants,id'],
            'variants.*.sku' => ['required_with:variants', 'string', 'max:100',
                \Illuminate\Validation\Rule::unique('product_variants', 'sku')->ignore($this->route('product')?->id, 'product_id'),
            ],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.price_adjustment' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
