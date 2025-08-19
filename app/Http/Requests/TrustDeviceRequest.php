<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrustDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ZTA/Keycloak gèrent l’accès
    }

    public function rules(): array
    {
        return [
            'fingerprint' => 'required|string|min:16|max:191',
            'device_name' => 'sometimes|string|max:191',
            'platform'    => 'sometimes|string|max:100',
            'user_agent'  => 'sometimes|string|max:2000',
            'mfa_passed'  => 'sometimes|boolean',
        ];
    }
}
