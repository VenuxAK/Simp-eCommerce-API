<?php

namespace App\Modules\Promotion\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and handles Update requests for Discount.
 */
class UpdateDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('discounts.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:percentage,fixed'],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'applies_to' => ['sometimes', 'in:all,category,product'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ];
    }
}
