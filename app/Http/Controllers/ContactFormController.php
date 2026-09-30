<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Jobs\CreateInquiryInCas;
use App\Jobs\SendInquiryMails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ContactFormController extends Controller
{
    private const MAX_SUBMISSIONS_PER_MINUTE = 1;

    private const MAX_SUBMISSIONS_PER_DAY = 3;

    public function store(ContactFormRequest $request): RedirectResponse
    {
        $minuteKey = 'contact-form:minute:'.$request->ip();
        $dayKey = 'contact-form:day:'.$request->ip();

        if (
            RateLimiter::tooManyAttempts($minuteKey, self::MAX_SUBMISSIONS_PER_MINUTE)
            || RateLimiter::tooManyAttempts($dayKey, self::MAX_SUBMISSIONS_PER_DAY)
        ) {
            return back()->withInput()->withErrors([
                'rate_limit' => 'Sie haben bereits eine Anfrage gesendet. Bitte versuchen Sie es später erneut oder rufen Sie uns an.',
            ]);
        }

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($dayKey, 86400);

        if ($request->isHoneypotTriggered()) {
            Log::channel('single')->info('Kontaktformular: Honeypot ausgelöst, Anfrage verworfen.', ['ip' => $request->ip()]);

            return redirect()->route('danke');
        }

        CreateInquiryInCas::dispatch(
            name: $request->validated('name'),
            email: $request->validated('email'),
            telefon: $request->validated('telefon'),
            plz: $request->validated('plz'),
            nachricht: $request->validated('nachricht'),
            praxis: $request->validated('praxis'),
            fachgebiet: $request->validated('fachgebiet'),
            wantsCallback: $request->wantsCallback(),
            rueckrufDatum: $request->validated('rueckruf_datum'),
        );

        SendInquiryMails::dispatch(
            name: $request->validated('name'),
            email: $request->validated('email'),
            telefon: $request->validated('telefon'),
            plz: $request->validated('plz'),
            nachricht: $request->validated('nachricht'),
            praxis: $request->validated('praxis'),
            fachgebiet: $request->validated('fachgebiet'),
            wantsCallback: $request->wantsCallback(),
            rueckrufDatum: $request->validated('rueckruf_datum'),
        );

        return redirect()->route('danke');
    }
}
