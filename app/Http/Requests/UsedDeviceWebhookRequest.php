<?php

namespace App\Http\Requests;

use App\Services\UsedDevices\UsedDeviceSynchronizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UsedDeviceWebhookRequest extends FormRequest
{
    /**
     * Authenticated by the EnsureValidCasWebhookToken middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * CAS may send the GUID in any case and with braces/dashes.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('gguid'))) {
            $this->merge(['gguid' => UsedDeviceSynchronizer::normalizeGuid($this->input('gguid'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gguid' => ['required', 'string', 'regex:/^[0-9A-F]{32}$/'],
            'action' => ['nullable', 'string', 'max:32'],
        ];
    }
}
