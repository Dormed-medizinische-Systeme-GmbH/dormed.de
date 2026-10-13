# resources/js

Zwei Module, beide über Vite gebündelt.

## Consent (`consent.js`)

`vanilla-cookieconsent` liefert nur die Logik (Kategorien, Zustand, Re-Prompt bei
Policy-Änderung). Das sichtbare Banner ist eigenes Markup
(`components/layout/consent-banner.blade.php`), gesteuert über die öffentliche API und
`data-consent-*`-Attribute.

- **Full-Build importieren, nicht den „core"-Build.** Der GUI-lose Build
  (`vanilla-cookieconsent/dist/core/…`) wirft beim `run()` einen Fehler (Upstream-Bug).
  Mit `autoShow: false` und dem Default `lazyHtmlGeneration` erzeugt der Full-Build kein
  eigenes Modal-DOM, solange `show()` und `showPreferences()` nie aufgerufen werden.
- **Zustand nur im Browser-Cookie** (Standard-Cookie der Library, `cc_cookie`; `consent.js`
  setzt keine eigene Cookie-Konfiguration), nie in der Laravel-Session: Sessions liegen in
  der SQLite-Datei, ein Cookie bleibt davon unabhängig gültig.
- **Kategorien** `necessary` (readOnly), `analytics`, `marketing`, ohne konkrete Dienste.
  `revision` hochzählen, sobald sich Kategorien oder Zweck ändern: Wer unter einer
  niedrigeren Revision zugestimmt hat, bekommt das Banner erneut.
- **Skripte hinter Consent:** Ein Skript mit `type="text/plain"` und `data-category="…"`
  führt die Library (`manageScriptTags`, Default an) erst aus, wenn die Kategorie
  akzeptiert ist, so beim Rybbit-Skript im Layout ([resources](../README.md)). Ohne
  Zustimmung oder bei „Nur notwendige" gibt es keinen Request an den Anbieter.
- **Drittanbieter-Embeds** (z. B. Maps) gate die Website nicht serverseitig über den
  Consent-Cookie. Bindet man eines ein, muss es erst nach Consent rendern (Skripte per
  `data-category`, Frames/Bilder gesondert).
- **Tests mit Playwright:** `hideFromBots` bleibt auf `true` (SEO). Playwright setzt
  `navigator.webdriver = true`, wodurch die Library Consent nie als gültig wertet. Per
  `page.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () =>
  false }))` gegensteuern, nicht `hideFromBots` ändern.

## Formular-Spamschutz (`form-guard.js`)

Browser-Hälfte von `App\Support\FormGuard` ([app](../../app/README.md#spamschutz-supportformguard-rules)).
Muster, Whitelist und Meldungen kommen aus PHP über das Attribut `data-form-guard` des
Formulars, damit Frontend und Backend nicht auseinanderlaufen; das Backend setzt alles
unabhängig davon durch. `window.dormedFormGuard.problems(form)` liefert dieselben
Meldungen für die eigene Submit-Validierung der Seiten.
