<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_email'        => ['sometimes', 'boolean'],
            'channel_whatsapp'     => ['sometimes', 'boolean'],
            'channel_push'         => ['sometimes', 'boolean'],
            'rate_limit_per_minute' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'quiet_hours_start'    => ['nullable', 'date_format:H:i'],
            'quiet_hours_end'      => ['nullable', 'date_format:H:i'],
        ];
    }
}
