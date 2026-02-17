<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'string', 'email', 'max:190', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'string', 'in:super_admin,admin'],
        ];
    }
}
