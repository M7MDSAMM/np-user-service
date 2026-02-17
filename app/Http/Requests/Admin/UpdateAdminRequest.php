<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = $this->route('admin')?->id ?? 0;

        return [
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'string', 'email', 'max:190', 'unique:admins,email,'.$adminId],
            'password' => ['nullable', 'string', 'min:8'],
            'role'     => ['required', 'string', 'in:super_admin,admin'],
        ];
    }
}
