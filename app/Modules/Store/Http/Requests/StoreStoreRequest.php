<?php

namespace App\Modules\Store\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates and handles Create requests for .
 */
class StoreStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Modules\Store\Models\Store::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:stores,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'json'],
        ];
    }
}
