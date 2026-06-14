<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageStoreUsers();
    }

    public function rules(): array
    {
        $allowedRoles = ['store_owner', 'store_manager', 'inventory_staff', 'sales_staff'];

        // Root can create users with any role; store_owner is limited to non-owner roles.
        if ($this->user()?->isStoreOwner()) {
            $allowedRoles = ['store_manager', 'inventory_staff', 'sales_staff'];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must include at least one uppercase letter, one lowercase letter, and one digit.',
            'store_id.required' => 'The store field is required.',
            'store_id.exists' => 'The selected store does not exist.',
        ];
    }
}
