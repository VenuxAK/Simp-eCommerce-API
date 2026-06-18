<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('products.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku'],
            'variants.*.size' => ['nullable', 'string', 'max:255'],
            'variants.*.color' => ['nullable', 'string', 'max:255'],
            'variants.*.price_adjustment' => ['nullable', 'numeric'],
            'variants.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
