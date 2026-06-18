<?php

namespace App\Modules\Sales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and handles Update requests for InvoiceStatus.
 */
class UpdateInvoiceStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('invoices.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:draft,issued,paid,cancelled,refunded'],
        ];
    }
}
