<?php

namespace App\Modules\Store\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and handles Create requests for .
 */
class UpdateStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isRoot() || $this->user()?->isStoreAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:stores,slug,' . $this->route('store')?->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'json'],
        ];
    }
}
