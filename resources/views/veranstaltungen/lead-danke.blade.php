<x-layout>
    <x-slot:head>
        <title>Danke | {{ $event['name'] }} | SONORING DORMED</title>
        <meta name="robots" content="noindex, nofollow">
    </x-slot:head>

<main class="main">
<style>
.lead-danke__wrap {
  font-family: 'Space Grotesk', sans-serif;
  -webkit-font-smoothing: antialiased;
  width: 100%;
  background: #fff;
  /* Header ist position:fixed mit 68px Höhe (--nav-h in style.css). */
  padding: calc(68px + 2.5rem) 1rem 3rem;
  text-align: center;
}
.lead-danke__inner { width: 100%; max-width: 520px; margin: 0 auto; }
.lead-danke__icon {
  width: 60px; height: 60px;
  margin: 0 auto 1.2rem;
  border-radius: 50%;
  background: #ECFDF5;
  border: 2px solid #A7F3D0;
  color: #10B981;
  font-size: 1.6rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}
.lead-danke__eyebrow {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.68rem;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #10B981;
  margin-bottom: 0.8rem;
}
.lead-danke__h1 {
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: -0.03em;
  line-height: 1.2;
  color: #0B1A2E;
  margin-bottom: 0.8rem;
}
.lead-danke__h1 span { color: #0B5FB7; }
.lead-danke__text { font-size: 0.95rem; line-height: 1.6; color: #48576F; margin-bottom: 1.8rem; }
.lead-danke__actions { display: flex; flex-direction: column; gap: 0.75rem; }
.lead-danke__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 52px;
  font-size: 1rem;
  font-weight: 700;
  padding: 0.9rem 1.4rem;
}
.lead-danke__btn--primary { color: #fff; background: linear-gradient(90deg, #093A7E, #3EB2F0); }
.lead-danke__btn--secondary { color: #0B5FB7; background: #fff; border: 1px solid rgba(9,58,126,0.25); }

@media (min-width: 640px) {
  .lead-danke__wrap { padding: calc(68px + 4rem) 1.5rem 5rem; }
  .lead-danke__h1 { font-size: 2rem; }
  .lead-danke__actions { flex-direction: row; justify-content: center; }
}
</style>

<section class="lead-danke__wrap">
  <div class="lead-danke__inner">
    <div class="lead-danke__icon" aria-hidden="true">&#10003;</div>
    <div class="lead-danke__eyebrow">{{ $event['name'] }}</div>
    <h1 class="lead-danke__h1">Vielen Dank —<br><span>wir melden uns bei Ihnen.</span></h1>
    <p class="lead-danke__text">Ihre Angaben sind bei uns eingegangen. Ihr persönlicher Ansprechpartner meldet sich nach der Veranstaltung direkt bei Ihnen.</p>

    <div class="lead-danke__actions">
      <a class="lead-danke__btn lead-danke__btn--primary" href="{{ route('veranstaltungen.lead.show', $slug) }}">Weiteren Kontakt erfassen</a>
      <a class="lead-danke__btn lead-danke__btn--secondary" href="{{ route('ultraschallgeraete.index') }}">Geräte entdecken</a>
    </div>
  </div>
</section>
</main>
</x-layout>
