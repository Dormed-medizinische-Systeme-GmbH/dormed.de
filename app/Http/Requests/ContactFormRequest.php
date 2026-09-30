<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:128'],
            'email' => ['required', 'email', 'max:64'],
            'telefon' => ['nullable', 'string', 'max:32'],
            'plz' => ['required', 'digits:5'],
            'nachricht' => ['nullable', 'string', 'max:5000'],
            'praxis' => ['nullable', 'string', 'max:64'],
            'fachgebiet' => ['nullable', 'string', 'max:64'],
            'rueckruf' => ['nullable', 'in:ja'],
            'rueckruf_datum' => ['nullable', 'date'],
            'datenschutz' => ['required', 'in:ja'],
            'website' => ['nullable', 'string'],
        ];
    }

    /**
     * Whether the hidden honeypot field was filled in, which only bots do.
     */
    public function isHoneypotTriggered(): bool
    {
        return filled($this->validated('website'));
    }

    /**
     * Whether the customer asked to be called back.
     */
    public function wantsCallback(): bool
    {
        return $this->validated('rueckruf') === 'ja';
    }
}
