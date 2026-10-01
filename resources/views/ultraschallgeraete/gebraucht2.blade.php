<x-layout>
    <x-slot:head>
        <title>Gebrauchte Ultraschallgeräte filtern | DORMED</title>
        <meta name="description" content="Geprüfte Gebrauchtgeräte von Mindray und Esaote nach Hersteller, Systemtyp und Baujahr filtern.">
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

<main id="yuuble-main" class="main">
<style>
.gb2__wrap {
  --blue-d: rgb(9,58,126); --blue-m: rgb(62,178,240);
  --text: rgb(18,30,52); --muted: rgb(72,87,112);
  --line: rgba(9,58,126,0.08); --grad: linear-gradient(90deg, var(--blue-d), var(--blue-m));
  font-family: 'Space Grotesk', sans-serif; -webkit-font-smoothing: antialiased;
  width: 100%; background: #F6F8FB;
}
.gb2__wrap *, .gb2__wrap *::before, .gb2__wrap *::after { box-sizing: border-box; margin: 0; padding: 0; }
.gb2__wrap *:focus-visible { outline: 2px solid var(--blue-d); outline-offset: 3px; }
.gb2__outer {
  max-width: 1440px; margin: 0 auto; padding: 8rem 2rem 6rem;
  display: grid; grid-template-columns: 220px 1fr; gap: 3rem; align-items: start;
}
.gb2__sidebar { position: sticky; top: 6rem; display: flex; flex-direction: column; gap: 1.8rem; }
.gb2__sidebar-title { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.03em; color: var(--text); }
.gb2__group { display: flex; flex-direction: column; gap: 0.15rem; }
.gb2__group-label {
  font-family: 'JetBrains Mono', monospace; font-size: 0.56rem; letter-spacing: 0.18em;
  text-transform: uppercase; color: rgba(9,58,126,0.45); margin-bottom: 0.4rem;
}
.gb2__btn {
  display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;
  width: 100%; background: none; border: none; border-left: 2px solid transparent;
  padding: 0.55rem 0.8rem; font: inherit; font-size: 0.88rem; color: var(--muted);
  text-align: left; cursor: pointer; transition: background 0.15s, color 0.15s;
}
.gb2__btn:hover { background: rgba(9,58,126,0.04); color: var(--text); }
.gb2__btn--active { border-left-color: var(--blue-d); background: #fff; color: var(--blue-d); font-weight: 600; }
.gb2__count { font-family: 'JetBrains Mono', monospace; font-size: 0.62rem; color: rgba(9,58,126,0.4); }
.gb2__result { font-size: 0.8rem; color: var(--muted); padding-top: 1rem; border-top: 1px solid var(--line); }
.gb2__reset {
  align-self: flex-start; background: none; border: none; font: inherit; font-size: 0.8rem;
  color: var(--blue-d); text-decoration: underline; cursor: pointer; display: none;
}
.gb2__reset--visible { display: inline; }

.gb2__header { margin-bottom: 2rem; display: flex; flex-direction: column; gap: 0.6rem; }
.gb2__eyebrow {
  font-family: 'JetBrains Mono', monospace; font-size: 0.58rem; letter-spacing: 0.22em;
  text-transform: uppercase; color: rgba(62,178,240,0.8);
}
.gb2__h1 { font-size: clamp(1.8rem, 3vw, 2.6rem); font-weight: 700; letter-spacing: -0.04em; line-height: 1.05; color: #0B1A2E; }
.gb2__h1 span { background: var(--grad); -webkit-background-clip: text; background-clip: text; color: transparent; }
.gb2__intro { font-size: 0.95rem; line-height: 1.7; color: var(--muted); max-width: 60ch; }

.gb2__cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
.gb2__card {
  background: #fff; border: 1px solid var(--line); display: flex; flex-direction: column;
  position: relative; overflow: hidden; transition: box-shadow 0.25s, transform 0.25s;
}
.gb2__card:hover { box-shadow: 0 8px 32px rgba(9,58,126,0.10); transform: translateY(-2px); }
.gb2__card--hidden { display: none; }
.gb2__card-img { position: relative; aspect-ratio: 4 / 3; background: #F4F6F9; display: flex; align-items: center; justify-content: center; }
.gb2__card-img img { width: 100%; height: 100%; object-fit: contain; padding: 1.2rem; }
.gb2__badge {
  position: absolute; top: 0.8rem; left: 0.8rem; font-family: 'JetBrains Mono', monospace;
  font-size: 0.54rem; letter-spacing: 0.12em; text-transform: uppercase; padding: 0.25rem 0.6rem;
  color: rgb(195,0,0); background: rgba(195,0,0,0.07); border: 1px solid rgba(195,0,0,0.15);
}
.gb2__badge--year { left: auto; right: 0.8rem; color: var(--blue-d); background: rgba(9,58,126,0.06); border-color: rgba(9,58,126,0.15); }
.gb2__card-body { display: flex; flex-direction: column; gap: 0.6rem; padding: 1.5rem 1.6rem 1.7rem; flex: 1; }
.gb2__card-title { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.02em; color: var(--text); }
.gb2__card-text { font-size: 0.88rem; line-height: 1.65; color: var(--muted); }
.gb2__features { list-style: none; display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.8rem; color: var(--muted); }
.gb2__features li::before { content: '·'; margin-right: 0.5rem; color: var(--blue-m); font-weight: 700; }
.gb2__card-cta {
  margin-top: auto; align-self: flex-start; font-size: 0.85rem; font-weight: 600; color: #fff;
  text-decoration: none; background: var(--grad); padding: 0.7rem 1.2rem;
  box-shadow: 0 4px 20px rgba(9,58,126,0.22); transition: opacity 0.2s, transform 0.15s;
}
.gb2__card-cta:hover { opacity: 0.92; transform: translateY(-1px); }

.gb2__empty { display: none; text-align: center; padding: 4rem 2rem; color: var(--muted); font-size: 0.9rem; line-height: 1.7; }
.gb2__empty--visible { display: block; }
.gb2__empty a { color: var(--blue-d); }

.gb2__mob { display: none; }
@media (max-width: 1100px) { .gb2__cards { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 860px) {
  .gb2__outer { grid-template-columns: 1fr; padding: 6rem 1.2rem 4rem; gap: 1.5rem; }
  .gb2__sidebar { display: none; }
  .gb2__mob { display: grid; grid-template-columns: 1fr; gap: 0.6rem; margin-bottom: 1.5rem; }
  .gb2__mob select {
    width: 100%; padding: 0.7rem 0.8rem; font: inherit; font-size: 0.9rem;
    border: 1px solid rgba(9,58,126,0.2); background: #fff; color: var(--text);
  }
  .gb2__mob-result { font-size: 0.8rem; color: var(--muted); }
}
@media (max-width: 600px) { .gb2__cards { grid-template-columns: 1fr; } }
</style>

<section class="gb2__wrap" id="gb2-filter" aria-label="Gebrauchte Ultraschallgeräte">
  <div class="gb2__outer">

    <aside class="gb2__sidebar" aria-label="Geräte filtern">
      <div class="gb2__sidebar-title">Gebrauchtgeräte</div>

      <div class="gb2__group">
        <div class="gb2__group-label">Hersteller</div>
        <button type="button" class="gb2__btn gb2__btn--active" data-filter="brand" data-value="all" aria-pressed="true">Alle Hersteller <span class="gb2__count">{{ $devices->count() }}</span></button>
        @foreach ($brandLabels as $brand => $label)
          <button type="button" class="gb2__btn" data-filter="brand" data-value="{{ $brand }}" aria-pressed="false">{{ $label }} <span class="gb2__count">{{ $brandCounts[$brand] }}</span></button>
        @endforeach
      </div>

      <div class="gb2__group">
        <div class="gb2__group-label">Systemtyp</div>
        <button type="button" class="gb2__btn gb2__btn--active" data-filter="system" data-value="all" aria-pressed="true">Alle Systeme</button>
        @foreach ($systems as $system => $label)
          @if (($systemCounts[$system] ?? 0) > 0)
            <button type="button" class="gb2__btn" data-filter="system" data-value="{{ $system }}" aria-pressed="false">{{ $label }} <span class="gb2__count">{{ $systemCounts[$system] }}</span></button>
          @endif
        @endforeach
      </div>

      <div class="gb2__group">
        <div class="gb2__group-label">Baujahr</div>
        <button type="button" class="gb2__btn gb2__btn--active" data-filter="year" data-value="all" aria-pressed="true">Alle Baujahre</button>
        @foreach ($yearBands as $band => $label)
          @if (($yearBandCounts[$band] ?? 0) > 0)
            <button type="button" class="gb2__btn" data-filter="year" data-value="{{ $band }}" aria-pressed="false">{{ $label }} <span class="gb2__count">{{ $yearBandCounts[$band] }}</span></button>
          @endif
        @endforeach
      </div>

      <div class="gb2__result" id="gb2-result" aria-live="polite">{{ $devices->count() }} {{ $devices->count() === 1 ? 'Gerät' : 'Geräte' }}</div>
      <button type="button" class="gb2__reset" id="gb2-reset">Filter zurücksetzen</button>
    </aside>

    <div>
      <header class="gb2__header">
        <span class="gb2__eyebrow">Ultraschallgeräte · Gebraucht &amp; Refurbished</span>
        <h1 class="gb2__h1">Aktuell verfügbare <span>Gebrauchtgeräte</span></h1>
        <p class="gb2__intro">Geprüft, aufbereitet und mit Garantie. Unser Bestand wechselt regelmäßig – fragen Sie ein Gerät direkt bei uns an.</p>
      </header>

      <div class="gb2__mob" aria-label="Geräte filtern">
        <select id="gb2-mob-brand" data-filter="brand" aria-label="Hersteller">
          <option value="all">Alle Hersteller</option>
          @foreach ($brandLabels as $brand => $label)
            <option value="{{ $brand }}">{{ $label }}</option>
          @endforeach
        </select>
        <select id="gb2-mob-system" data-filter="system" aria-label="Systemtyp">
          <option value="all">Alle Systeme</option>
          @foreach ($systems as $system => $label)
            @if (($systemCounts[$system] ?? 0) > 0)
              <option value="{{ $system }}">{{ $label }}</option>
            @endif
          @endforeach
        </select>
        <select id="gb2-mob-year" data-filter="year" aria-label="Baujahr">
          <option value="all">Alle Baujahre</option>
          @foreach ($yearBands as $band => $label)
            @if (($yearBandCounts[$band] ?? 0) > 0)
              <option value="{{ $band }}">{{ $label }}</option>
            @endif
          @endforeach
        </select>
        <span class="gb2__mob-result" id="gb2-mob-result">{{ $devices->count() }} {{ $devices->count() === 1 ? 'Gerät' : 'Geräte' }}</span>
      </div>

      <div class="gb2__cards" role="list">
        @foreach ($devices as $device)
          <article class="gb2__card" role="listitem" data-brand="{{ $device->brand }}" data-system="{{ $device->system }}" data-year="{{ $device->yearBand }}">
            <div class="gb2__card-img">
              <img src="{{ $device->imageUrl ?? '/assets/img/platzhalter-geraet.svg' }}" alt="{{ $device->brandLabel }} {{ $device->name }}" loading="lazy">
              <span class="gb2__badge">{{ $device->brandLabel }}</span>
              @if ($device->year)
                <span class="gb2__badge gb2__badge--year">{{ $device->year }}</span>
              @endif
            </div>
            <div class="gb2__card-body">
              <h3 class="gb2__card-title">{{ $device->name }}</h3>
              <p class="gb2__card-text">{{ $device->description ?: $device->systemLabel }}</p>
              @if ($device->probes)
                <ul class="gb2__features">
                  @foreach ($device->probes as $probe)
                    <li>{{ $probe }}</li>
                  @endforeach
                </ul>
              @endif
              <a class="gb2__card-cta" href="{{ route('kontakt') }}?{{ http_build_query(['geraet' => $device->name, 'utm_source' => 'gebraucht', 'utm_medium' => 'produktkarte', 'utm_campaign' => 'geraet-anfrage']) }}">Gerät anfragen</a>
            </div>
          </article>
        @endforeach
      </div>

      <div class="gb2__empty{{ $devices->isEmpty() ? ' gb2__empty--visible' : '' }}" id="gb2-empty" role="status" aria-live="polite">
        Keine Geräte gefunden.<br>Filter zurücksetzen oder <a href="{{ route('kontakt') }}">persönlich beraten lassen</a>.
      </div>
    </div>

  </div>
</section>

<script>
(function () {
  var wrap = document.getElementById('gb2-filter');
  if (!wrap) return;
  var cards = wrap.querySelectorAll('.gb2__card');
  var resultEls = [document.getElementById('gb2-result'), document.getElementById('gb2-mob-result')];
  var emptyEl = document.getElementById('gb2-empty');
  var resetEl = document.getElementById('gb2-reset');
  var active = { brand: 'all', system: 'all', year: 'all' };

  function apply() {
    var visible = 0;
    cards.forEach(function (card) {
      var show = Object.keys(active).every(function (key) {
        return active[key] === 'all' || card.getAttribute('data-' + key) === active[key];
      });
      card.classList.toggle('gb2__card--hidden', !show);
      if (show) visible++;
    });
    var text = visible + (visible === 1 ? ' Gerät' : ' Geräte');
    resultEls.forEach(function (el) { if (el) el.textContent = text; });
    emptyEl.classList.toggle('gb2__empty--visible', visible === 0);
    resetEl.classList.toggle('gb2__reset--visible', Object.keys(active).some(function (key) { return active[key] !== 'all'; }));
  }

  function set(key, value) {
    active[key] = value;
    wrap.querySelectorAll('button[data-filter="' + key + '"]').forEach(function (btn) {
      var isActive = btn.getAttribute('data-value') === value;
      btn.classList.toggle('gb2__btn--active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    var select = wrap.querySelector('select[data-filter="' + key + '"]');
    if (select) select.value = value;
    apply();
  }

  wrap.querySelectorAll('button[data-filter]').forEach(function (btn) {
    btn.addEventListener('click', function () { set(btn.getAttribute('data-filter'), btn.getAttribute('data-value')); });
  });
  wrap.querySelectorAll('select[data-filter]').forEach(function (select) {
    select.addEventListener('change', function () { set(select.getAttribute('data-filter'), select.value); });
  });
  resetEl.addEventListener('click', function () { Object.keys(active).forEach(function (key) { set(key, 'all'); }); });
})();
</script>
</main>
</x-layout>
