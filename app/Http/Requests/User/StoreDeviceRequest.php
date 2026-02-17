<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required', 'string', 'max:255', 'unique:user_devices,token'],
            'provider' => ['sometimes', 'string', 'in:fcm'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ];
    }
}
