<?php

namespace App\Modules\Catalog\Http\Requests;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Brand::class) ?? false;
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
