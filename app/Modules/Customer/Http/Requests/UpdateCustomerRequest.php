<?php

namespace App\Modules\Customer\Http\Requests;

use App\Modules\Customer\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and handles Update requests for Customer.
 */
class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', Customer::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email,'.($this->route('customer')?->id ?? $this->route('customer'))],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ];
    }
}
