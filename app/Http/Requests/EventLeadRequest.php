<?php

namespace App\Http\Requests;

use App\Rules\AllowedScriptsOnly;
use App\Rules\NoForeignLinks;
use App\Support\FormGuard;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class EventLeadRequest extends FormRequest
{
    /**
     * Same spam protection as the contact form (see ContactFormRequest):
     * no foreign links in free-text fields, Latin script in the message.
     *
     * @var array<int, string>
     */
    public const LINK_FIELDS = ['praxis_name', 'strasse', 'ort', 'interesse', 'altgeraet_system', 'budget', 'nachricht'];

    /**
     * @var array<int, string>
     */
    public const SCRIPT_FIELDS = ['nachricht'];

    /**
     * Single choice "Finanzierungsform": form value => label.
     *
     * @var array<string, string>
     */
    public const FINANCING_OPTIONS = [
        'barkauf' => 'Barkauf',
        'leasing' => 'Leasing',
        'full_service' => 'Full-Service',
    ];

    /**
     * Multiple choice "Dienstleistungen": form value => label.
     *
     * @var array<string, string>
     */
    public const SERVICE_OPTIONS = [
        'garantieverlaengerung' => 'Garantieverlängerung',
        'elektronikversicherung' => 'Elektronikversicherung',
        'netzwerkanbindung' => 'Netzwerkanbindung',
        'wartung' => 'Wartung',
    ];

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
            'altgeraet' => ['nullable', 'in:ja'],
            'altgeraet_system' => ['nullable', 'string', 'max:128', new NoForeignLinks],
            'budget' => ['nullable', 'string', 'max:64', new NoForeignLinks],
            'anschaffungsdatum' => ['nullable', 'date_format:Y-m-d'],
            'finanzierung' => ['nullable', Rule::in(array_keys(self::FINANCING_OPTIONS))],
            'dienstleistungen' => ['nullable', 'array'],
            'dienstleistungen.*' => [Rule::in(array_keys(self::SERVICE_OPTIONS))],
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
            'anschaffungsdatum.date_format' => 'Bitte geben Sie ein gültiges Anschaffungsdatum an.',
            'finanzierung.in' => 'Bitte wählen Sie eine gültige Finanzierungsform.',
            'dienstleistungen.*.in' => 'Bitte wählen Sie gültige Dienstleistungen.',
        ];
    }

    /**
     * Whether the hidden honeypot field was filled in, which only bots do.
     */
    public function isHoneypotTriggered(): bool
    {
        return filled($this->validated('website'));
    }

    public function wantsOldDeviceTradeIn(): bool
    {
        return $this->validated('altgeraet') === 'ja';
    }

    /**
     * The current system is only relevant when the trade-in box is ticked;
     * text left in the (hidden) field otherwise is dropped.
     */
    public function oldDeviceSystem(): ?string
    {
        return $this->wantsOldDeviceTradeIn() ? $this->validated('altgeraet_system') : null;
    }

    /**
     * Planned purchase date as shown in the mail (d.m.Y).
     */
    public function plannedPurchaseDate(): ?string
    {
        $date = $this->validated('anschaffungsdatum');

        return $date !== null ? Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y') : null;
    }

    public function financingLabel(): ?string
    {
        return self::FINANCING_OPTIONS[$this->validated('finanzierung')] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function serviceLabels(): array
    {
        return collect($this->validated('dienstleistungen') ?? [])
            ->unique()
            ->map(fn (string $service): string => self::SERVICE_OPTIONS[$service])
            ->values()
            ->all();
    }
}
