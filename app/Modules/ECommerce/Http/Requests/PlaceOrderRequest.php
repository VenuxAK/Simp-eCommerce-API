<?php

namespace App\Modules\ECommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a checkout order request.
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required', 'exists:addresses,id'],
            'payment_method' => ['sometimes', 'string', 'in:cash,stripe'],
            'payment_intent_id' => ['nullable', 'string', 'required_if:payment_method,stripe'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
