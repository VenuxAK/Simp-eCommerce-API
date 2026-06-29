<?php

namespace App\Modules\ECommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncCartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
