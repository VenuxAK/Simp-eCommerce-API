<?php

namespace App\Modules\Store\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin();
    }

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
