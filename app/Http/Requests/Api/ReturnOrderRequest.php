<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReturnOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $orderId = $this->route('order')?->id;

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', \Illuminate\Validation\Rule::exists('order_items', 'id')->where('order_id', $orderId)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
