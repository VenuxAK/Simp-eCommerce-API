<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageStoreUsers();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        $allowedRoles = ['store_owner', 'store_manager', 'inventory_staff', 'sales_staff'];

        if ($this->user()?->isStoreOwner()) {
            $allowedRoles = ['store_manager', 'inventory_staff', 'sales_staff'];
        }

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$userId],
            'password' => ['nullable', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
            'role' => ['sometimes', 'string', Rule::in($allowedRoles)],
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must include at least one uppercase letter, one lowercase letter, and one digit.',
        ];
    }
}
