<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRoot() || $this->user()?->isStoreAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('brands', 'name')->ignore($this->route('brand')),
            ],
            'logo' => ['nullable', 'string', 'max:255'],
        ];
    }
}
