<?php

namespace App\Http\Requests;

use App\Rules\AllowedScriptsOnly;
use App\Rules\NoForeignLinks;
use App\Support\FormGuard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EventLeadRequest extends FormRequest
{
    /**
     * Same spam protection as the contact form (see ContactFormRequest):
     * no foreign links in free-text fields, Latin script in the message.
     *
     * @var array<int, string>
     */
    public const LINK_FIELDS = ['praxis_name', 'strasse', 'ort', 'interesse', 'budget', 'nachricht'];

    /**
     * @var array<int, string>
     */
    public const SCRIPT_FIELDS = ['nachricht'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(FormGuard::sanitize($this->all(), self::LINK_FIELDS));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'praxis_name' => ['required', 'string', 'max:128', new NoForeignLinks],
            'strasse' => ['nullable', 'string', 'max:128', new NoForeignLinks],
            'plz' => ['nullable', 'digits:5'],
            'ort' => ['nullable', 'string', 'max:64', new NoForeignLinks],
            'email' => ['nullable', 'required_without:telefon', 'email', 'max:64'],
            'telefon' => ['nullable', 'required_without:email', 'string', 'max:32'],
            'interesse' => ['nullable', 'string', 'max:500', new NoForeignLinks],
            'budget' => ['nullable', 'string', 'max:64', new NoForeignLinks],
            'nachricht' => ['nullable', 'string', 'max:5000', new NoForeignLinks, new AllowedScriptsOnly],
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
