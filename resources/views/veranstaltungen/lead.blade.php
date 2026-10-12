<x-layout>
    <x-slot:head>
        <title>Kontakt {{ $event['name'] }} | SONORING DORMED</title>
        <meta name="robots" content="noindex, nofollow">
        @vite('resources/js/form-guard.js')
    </x-slot:head>

<main class="main">
<style>
/* Mobile first: eine Spalte, ab 640px PLZ/Ort und E-Mail/Telefon nebeneinander.
   Eingabefelder mit 16px Schrift, damit iOS beim Fokus nicht hineinzoomt. */
.lead__wrap {
  font-family: 'Space Grotesk', sans-serif;
  -webkit-font-smoothing: antialiased;
  width: 100%;
  background: #fff;
  /* Header ist position:fixed mit 68px Höhe (--nav-h in style.css). */
  padding: calc(68px + 1.75rem) 1rem 3rem;
}
.lead__inner { width: 100%; max-width: 560px; margin: 0 auto; }
.lead__eyebrow {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.68rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #0B5FB7;
  margin-bottom: 0.6rem;
}
.lead__h1 {
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  line-height: 1.15;
  color: #0B1A2E;
  margin-bottom: 0.6rem;
}
.lead__h1 span { color: #0B5FB7; }
.lead__sub { font-size: 0.95rem; line-height: 1.55; color: #48576F; margin-bottom: 1.5rem; }

.lead__row { display: grid; grid-template-columns: 1fr; gap: 0.85rem; margin-bottom: 0.85rem; }
.lead__field { display: flex; flex-direction: column; gap: 0.35rem; }
.lead__label {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.66rem;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(9,58,126,0.7);
  font-weight: 500;
}
.lead__label--req::after { content: ' *'; color: rgb(220,38,38); font-weight: 700; }
.lead__hint { font-size: 0.8rem; color: rgba(9,58,126,0.68); line-height: 1.4; margin: -0.35rem 0 0.85rem; }

.lead__input,
.lead__textarea {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 16px;
  color: #0B1A2E;
  background: #F7F9FC;
  border: 1px solid rgba(9,58,126,0.15);
  border-radius: 0;
  padding: 0.85rem 0.95rem;
  min-height: 48px;
  outline: none;
  width: 100%;
  -webkit-appearance: none;
  appearance: none;
  transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.lead__input:focus,
.lead__textarea:focus {
  border-color: #0B5FB7;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(11,95,183,0.08);
}
.lead__input[aria-invalid="true"],
.lead__textarea[aria-invalid="true"] { border-color: rgba(220,38,38,0.55); background: rgba(220,38,38,0.02); }
.lead__input::placeholder,
.lead__textarea::placeholder { color: rgba(9,58,126,0.42); }
.lead__textarea { resize: vertical; min-height: 110px; line-height: 1.6; }

.lead__check { display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; margin-top: 1.1rem; }
.lead__check input[type="checkbox"] {
  width: 22px; height: 22px;
  flex-shrink: 0;
  margin-top: 0.05rem;
  accent-color: #0B5FB7;
  cursor: pointer;
}
.lead__check-label { font-size: 0.88rem; color: rgb(72,87,112); line-height: 1.55; }
.lead__check-label a { color: #0B5FB7; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }

.lead__errors {
  font-size: 0.88rem;
  line-height: 1.5;
  color: rgb(185,28,28);
  background: rgba(220,38,38,0.05);
  border: 1px solid rgba(220,38,38,0.25);
  padding: 0.85rem 1rem;
  margin-bottom: 1.1rem;
}

.lead__submit {
  width: 100%;
  min-height: 56px;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 1.05rem;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(90deg, #093A7E, #3EB2F0);
  border: none;
  padding: 1rem 1.4rem;
  margin-top: 1.4rem;
  cursor: pointer;
  box-shadow: 0 6px 24px rgba(11,95,183,0.28), 0 2px 6px rgba(11,95,183,0.15);
}
.lead__submit:active { opacity: 0.9; }

.lead__closed { font-size: 0.95rem; line-height: 1.6; color: #48576F; }
.lead__closed a { color: #0B5FB7; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }

@media (min-width: 640px) {
  .lead__wrap { padding: calc(68px + 3rem) 1.5rem 4rem; }
  .lead__h1 { font-size: 2rem; }
  .lead__row--split { grid-template-columns: 1fr 1fr; }
  .lead__row--plz { grid-template-columns: 140px 1fr; }
}
</style>

<section class="lead__wrap">
  <div class="lead__inner">
    <div class="lead__eyebrow">{{ $event['name'] }} · {{ $event['location'] }}</div>
    <h1 class="lead__h1">Schön, dass Sie <span>da waren.</span></h1>

    @if ($isAcceptingLeads)
      <p class="lead__sub">Hinterlassen Sie uns Ihre Kontaktdaten — Ihr persönlicher Ansprechpartner meldet sich nach der Veranstaltung bei Ihnen.</p>

      <form id="lead-form" method="POST" action="{{ route('veranstaltungen.lead.store', $slug) }}" data-form-guard="{{ json_encode(\App\Support\FormGuard::frontendConfig(\App\Http\Requests\EventLeadRequest::LINK_FIELDS, \App\Http\Requests\EventLeadRequest::SCRIPT_FIELDS)) }}">
        @csrf

        <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
          <label for="lead-website">Website</label>
          <input id="lead-website" name="website" type="text" tabindex="-1" autocomplete="off" value=""/>
        </div>

        <div class="lead__errors" id="lead-errors" role="alert" @if (! $errors->any()) hidden @endif>
          @foreach (array_unique($errors->all()) as $message)
            <div>&bull; {{ $message }}</div>
          @endforeach
        </div>

        <div class="lead__row">
          <div class="lead__field">
            <label class="lead__label lead__label--req" for="lead-praxis-name">Praxis / Name</label>
            <input class="lead__input" id="lead-praxis-name" name="praxis_name" type="text" placeholder="Praxis Dr. Mustermann" value="{{ old('praxis_name') }}" required autocomplete="organization" @if ($errors->has('praxis_name')) aria-invalid="true" @endif/>
          </div>
        </div>

        <div class="lead__row">
          <div class="lead__field">
            <label class="lead__label" for="lead-strasse">Straße</label>
            <input class="lead__input" id="lead-strasse" name="strasse" type="text" placeholder="Musterstraße 1" value="{{ old('strasse') }}" autocomplete="street-address" @if ($errors->has('strasse')) aria-invalid="true" @endif/>
          </div>
        </div>

        <div class="lead__row lead__row--plz">
          <div class="lead__field">
            <label class="lead__label" for="lead-plz">PLZ</label>
            <input class="lead__input" id="lead-plz" name="plz" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" placeholder="44269" value="{{ old('plz') }}" autocomplete="postal-code" @if ($errors->has('plz')) aria-invalid="true" @endif/>
          </div>
          <div class="lead__field">
            <label class="lead__label" for="lead-ort">Ort</label>
            <input class="lead__input" id="lead-ort" name="ort" type="text" placeholder="Dortmund" value="{{ old('ort') }}" autocomplete="address-level2" @if ($errors->has('ort')) aria-invalid="true" @endif/>
          </div>
        </div>

        <div class="lead__row lead__row--split">
          <div class="lead__field">
            <label class="lead__label" for="lead-email">E-Mail</label>
            <input class="lead__input" id="lead-email" name="email" type="email" inputmode="email" placeholder="praxis@example.de" value="{{ old('email') }}" autocomplete="email" @if ($errors->has('email')) aria-invalid="true" @endif/>
          </div>
          <div class="lead__field">
            <label class="lead__label" for="lead-telefon">Telefon</label>
            <input class="lead__input" id="lead-telefon" name="telefon" type="tel" placeholder="0231 / 188-600" value="{{ old('telefon') }}" autocomplete="tel" @if ($errors->has('telefon')) aria-invalid="true" @endif/>
          </div>
        </div>
        <p class="lead__hint">Bitte mindestens E-Mail oder Telefon angeben.</p>

        <div class="lead__row">
          <div class="lead__field">
            <label class="lead__label" for="lead-interesse">Interesse an</label>
            <input class="lead__input" id="lead-interesse" name="interesse" type="text" placeholder="z. B. Mindray TE 7, Schilddrüsen-Sonographie" value="{{ old('interesse') }}" autocomplete="off" @if ($errors->has('interesse')) aria-invalid="true" @endif/>
          </div>
        </div>

        <div class="lead__row">
          <div class="lead__field">
            <label class="lead__label" for="lead-budget">Budget</label>
            <input class="lead__input" id="lead-budget" name="budget" type="text" placeholder="z. B. ca. 30.000 €" value="{{ old('budget') }}" autocomplete="off" @if ($errors->has('budget')) aria-invalid="true" @endif/>
          </div>
        </div>

        <div class="lead__row">
          <div class="lead__field">
            <label class="lead__label" for="lead-nachricht">Nachricht</label>
            <textarea class="lead__textarea" id="lead-nachricht" name="nachricht" placeholder="Weitere Wünsche oder Fragen (optional)" @if ($errors->has('nachricht')) aria-invalid="true" @endif>{{ old('nachricht') }}</textarea>
          </div>
        </div>

        <label class="lead__check">
          <input type="checkbox" name="datenschutz" value="ja" required @checked(old('datenschutz') === 'ja') @if ($errors->has('datenschutz')) aria-invalid="true" @endif/>
          <span class="lead__check-label">Ich habe die <a href="{{ route('datenschutz') }}" target="_blank">Datenschutzerklärung</a> gelesen und stimme der Verarbeitung meiner Daten zu. <span style="color:rgb(220,38,38)">*</span></span>
        </label>

        <button type="submit" class="lead__submit">Kontaktdaten senden</button>
      </form>

      <script>
        // Spamschutz vor dem Absenden (resources/js/form-guard.js) - dieselben
        // Regeln lehnt auch das Backend ab, das hier spart nur den Roundtrip.
        document.getElementById('lead-form').addEventListener('submit', function (event) {
          var problems = window.dormedFormGuard ? window.dormedFormGuard.problems(this) : [];
          if (problems.length === 0) {
            return;
          }
          event.preventDefault();
          var errorBox = document.getElementById('lead-errors');
          errorBox.textContent = '';
          problems.forEach(function (problem) {
            var line = document.createElement('div');
            line.textContent = '\u2022 ' + problem.message;
            errorBox.appendChild(line);
            problem.field.setAttribute('aria-invalid', 'true');
          });
          errorBox.hidden = false;
          problems[0].field.focus();
          errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
      </script>
    @else
      <p class="lead__closed">
        Die Veranstaltung ist vorbei und das Formular nicht mehr aktiv. Sie erreichen uns jederzeit
        über unser <a href="{{ route('kontakt') }}">Kontaktformular</a> oder telefonisch unter
        <a href="tel:+4923118860">02301 / 188-600</a>.
      </p>
    @endif
  </div>
</section>
</main>
</x-layout>
