<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? 0;

        return [
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'string', 'email', 'max:190', 'unique:recipient_users,email,' . $userId],
            'phone_e164' => ['nullable', 'string', 'max:25', 'regex:/^\+[1-9]\d{1,14}$/'],
            'locale'    => ['sometimes', 'string', 'max:10'],
            'timezone'  => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
