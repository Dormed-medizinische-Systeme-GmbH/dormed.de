<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EventLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'praxis_name' => ['required', 'string', 'max:128'],
            'strasse' => ['nullable', 'string', 'max:128'],
            'plz' => ['nullable', 'digits:5'],
            'ort' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'required_without:telefon', 'email', 'max:64'],
            'telefon' => ['nullable', 'required_without:email', 'string', 'max:32'],
            'interesse' => ['nullable', 'string', 'max:500'],
            'budget' => ['nullable', 'string', 'max:64'],
            'nachricht' => ['nullable', 'string', 'max:5000'],
            'datenschutz' => ['required', 'in:ja'],
            'website' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'Bitte geben Sie eine E-Mail-Adresse oder eine Telefonnummer an.',
            'telefon.required_without' => 'Bitte geben Sie eine E-Mail-Adresse oder eine Telefonnummer an.',
        ];
    }

    /**
     * Whether the hidden honeypot field was filled in, which only bots do.
     */
    public function isHoneypotTriggered(): bool
    {
        return filled($this->validated('website'));
    }
}
