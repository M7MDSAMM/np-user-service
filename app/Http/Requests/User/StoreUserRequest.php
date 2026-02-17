<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'string', 'email', 'max:190', 'unique:recipient_users,email'],
            'phone_e164' => ['nullable', 'string', 'max:25', 'regex:/^\+[1-9]\d{1,14}$/'],
            'locale'    => ['sometimes', 'string', 'max:10'],
            'timezone'  => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
