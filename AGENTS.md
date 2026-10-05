# AGENTS.md — dormed.de

Allgemeine Spezifikationen für jeden Agenten (Claude oder anderer), der an diesem Repo
arbeitet. Bewusst so geschrieben, dass es **eigenständig** funktioniert: `ROADMAP.md` ist
aktuell noch der verbindliche Phasenplan für offene Phasen, wird nach Projektabschluss aber
voraussichtlich archiviert oder gelöscht und dann nicht mehr automatisch geladen. Alle
Informationen aus der Roadmap, die projektweit (nicht nur für eine einzelne Phase) gültig
sind, stehen deshalb hier vollständig — nicht nur als Verweis dorthin.

## Projektüberblick

dormed.de ist die Website der SONORING DORMED med. Systeme GmbH (Ultraschallgeräte-
Fachhändler). Das Projekt ist eine Übernahme von einer Vorgänger-Agentur, deren Vertrag
ausgelaufen ist. Der ursprüngliche Code war der statische Export eines No-Code-Website-
Builders ("Yuuble", erkennbar an `data-project-id`/`data-site-id`/`data-context-id`,
`data-engine="@yuuble/engine"`, `class="yuuble-engine-node"`) — kein aktives SDK, reines
Vanilla-JS pro Widget inline eingebettet. Wird schrittweise nach Laravel migriert, siehe
`ROADMAP.md` für den aktuellen Phasenstand.

**Architektur-Entscheidung: Laravel** (nicht SvelteKit/Inertia), Begründung:

- Kein SPA-Unterbau nötig, die Seite ist inhaltlich ein Content-/Produktkatalog.
- Backend-Logik wird laut Auftraggeber mittelfristig "heavy" (Webhooks, Scheduler,
  eventuell Queues, externe API-Anbindung) — genau Laravels Stärke (Cache, Queue,
  Scheduler, HTTP-Client, Events out of the box), ohne eine Business-Datenbank zu brauchen.
- Klassisches PHP-FPM-Hosting hat keinen selbst zu betreibenden Dauerprozess (Core Rule 5).
  SvelteKit-SSR bräuchte einen gemanagten Node-Prozess oder ein Serverless-Target.
- Blade/Alpine statt Inertia+Svelte: kein zusätzlicher Node-SSR-Prozess neben Laravel,
  passt besser zu einer 1:1-Styling-Migration von rohem HTML/CSS.

Es gibt zusätzlich zwei weitere Kontext-Quellen, die zusammen mit diesem Dokument den
vollständigen Einstiegspunkt bilden:

- **`CLAUDE.md`** — von `laravel/boost` generierte, Laravel/Pest/Pint-spezifische
  Coding-Guidelines (PHP-Codestil, Testing-Konventionen, wie Boost-MCP-Tools zu nutzen
  sind usw.). Technischer Coding-Standard, kein Projektkontext — ergänzt dieses Dokument,
  überschneidet sich nicht damit.
- **Laravel Boost** (`laravel/boost`, als Dev-Dependency installiert) — stellt einen
  MCP-Server mit Laravel-spezifischen Tools bereit (u. a. `search-docs` für
  versionsgenaue Doku, `database-query`/`database-schema`, `get-absolute-url`,
  `browser-logs`) sowie zwei Skills (`laravel-best-practices`, `pest-testing`) unter
  `.claude/skills/`. Bei Laravel-/Pest-Aufgaben diese Tools/Skills bevorzugen statt
  manueller Shell-Kommandos, siehe `CLAUDE.md` für Details.

## Nicht verhandelbare Leitplanken (Core Rules)

1. **Keine URL/Route darf sich in irgendeiner Phase ändern.** Die Seite ist seit Jahren
   online, Google kennt sie exakt so, wie sie aktuell vorliegt.
2. `sitemap.xml`/`sitemap-system-pages.xml` werden über Routen in `routes/web.php`
   ausgeliefert (Inhalt aus `resources/sitemap/`), **nicht** als statische Dateien aus
   `public/`.
3. Styling wird 1:1 optisch übernommen. Keine Redesigns, keine "Verbesserungen" nebenbei.
4. **Keine Business-/Content-Datenbank** (keine Produktdaten, keine Formular-Einträge o. ä.
   dauerhaft in einer DB). **Ausnahme:** SQLite als reiner, serverloser Zwischenspeicher für
   Framework-internen Zustand (Cache, Sessions) ist erlaubt — kein DB-Server-Prozess, keine
   Zugangsdaten, keine Backup-Pflicht. Umgesetzt über `DB_CONNECTION=sqlite`,
   `SESSION_DRIVER=database`, `CACHE_STORE=database`. Die Datei ist ephemer (gitignored),
   wird bei Bedarf per `migrate:fresh` neu aufgebaut. Begründung: auf klassischem
   PHP-FPM-Hosting sammeln sich bei dateibasierten Sessions/Cache sehr viele Einzeldateien
   an — SQLite bündelt das in einer Datei, ohne einen eigenen DB-Server-Prozess zu
   brauchen.
5. **Kein dauerhaft laufender Node/Server-Prozess.** Klassisches PHP-FPM-Hosting.
   `npm run build` läuft nur beim Deploy, nie als Runtime-Prozess.
6. **Das an den Browser ausgelieferte HTML darf sich in keinem Punkt inhaltlich vom
   ursprünglichen Yuuble-Export unterscheiden** — Ausnahmen nur: zentraler Stylesheet-
   Import statt Inline-`<style>`, entfernte Yuuble-Kommentare/-Metadaten, veränderte
   Klassen-/ID-Namen (kein `yb-`/`yb_`/`yu-`-Präfix mehr). Alles andere bleibt 1:1
   identisch: JSON-LD strukturierte Daten, SEO-Meta-Tags (title, description, canonical,
   OG/Twitter), Überschriftenstruktur (h1–h6) inklusive Text, alle sichtbaren Textinhalte.

## Umgebungen & Netzwerkzugriff

- **`dormed.de`** — die aktuell live geschaltete Seite, Hosting läuft noch über die
  Vorgänger-Agentur (Sonderregelung, bis die Übergabe abgeschlossen ist). Nur als
  **Read-only-Referenzquelle** nutzen, z. B. um fehlenden Content 1:1 abzugleichen. Niemals
  als Deploy-Ziel behandeln — wir haben dort keinen Schreibzugriff und sollen auch keinen
  Änderungsanspruch daran haben.
- **`dormed.everding.it`** — Staging-Deployment auf Coolify, gehört dem Auftraggeber
  (Lukas). Er redeployed dort manuell nach relevanten Pushes. **Änderungen im Repo
  erscheinen dort nicht automatisch** — vor einer Ferndiagnose auf dieser Domain im
  Zweifel erst nachfragen, ob schon redeployed wurde. **Geplante Ablösung:** sobald das
  Projekt eine gewisse Struktur erreicht hat, wird diese Domain durch `test.dormed.de`
  ersetzt. Der `claude`-Branch bleibt dann dauerhaft die Dev-Umgebung (Coolify), aber
  über einen GitHub-Actions-Workflow getriggert statt über Coolifys eigene
  Branch-Watch-Funktion (Details siehe Abschnitt "CI/CD & Launch" unten) — kein manuelles
  Redeployen mehr nötig. Diesen Abschnitt aktualisieren, sobald der Wechsel stattgefunden
  hat.
- Netzwerkzugriff auf neue/andere Domains kann durch die Egress-Policy der Session
  blockiert sein (403 auf CONNECT-Ebene, bevor überhaupt ein Request rausgeht). Das ist
  keine Website-seitige Blockade — nicht umgehen, sondern melden und ggf. um Freigabe
  bitten.

## Lokal testen & verifizieren

- Statischer Server im Repo-Root (Referenzvergleich gegen `.old/`): `python3 -m http.server
  <port>`. Laravel-Dev-Server: `php artisan serve --port=<port>`.
- Playwright ist in dieser Umgebung global installiert, nicht als Projekt-Dependency.
  Node-Skripte brauchen `NODE_PATH=/opt/node22/lib/node_modules node <script>.js` (Pfad
  kann je nach Umgebung abweichen — `npm root -g` prüfen, falls das nicht passt).
- Test-/Hilfsskripte gehören in ein Scratch-/Temp-Verzeichnis, **nicht ins Repo committen**.
- **Vor jeder Änderung an bestehenden Seiten:** Screenshot-Vergleich vorher/nachher
  (fullPage, Transitions/Animationen per injiziertem CSS abschalten, Seite einmal
  durchscrollen um scroll-reveal-Effekte zu triggern, erst danach screenshotten — sonst
  entstehen Diffs durch Timing statt durch echte Regressionen).
- `laravel/boost` (Dev-Dependency) injiziert in `local`/`debug`-Umgebungen ein
  Browser-Log-Capture-Script in jede Response (`InjectBoost`-Middleware) — erzeugt in
  Screenshot-Diffs sichtbares Pixel-Rauschen (Text-Antialiasing) unabhängig vom
  tatsächlichen Seiteninhalt. Bei `composer install --no-dev` bzw. außerhalb
  `local`/`debug` taucht das Script gar nicht auf. Für zuverlässige Vergleiche
  **Byte-/Text-Diff** (Script rausfiltern) nutzen, nicht blind auf Pixel-Diff verlassen —
  bei größeren Seitenmengen zusätzlich sichtbaren Text, Meta-Tags und JSON-LD (geparst,
  nicht als Rohstring) separat vergleichen statt nur den kompletten HTML-Byte-Diff, um
  erwartete Unterschiede (s. o.) von echten Regressionen zu trennen.
- **Nach jeder Änderung:** auf fehlgeschlagene Netzwerk-Requests (Status ≥ 400) und
  JS-Fehler prüfen (`page.on('response', ...)` / `page.on('pageerror', ...)`).
- Erst committen, wenn Screenshot-/Text-Diff und Request-/Fehler-Check beide sauber sind.

## Konventionen im Code

- Keine `yb-`/`yb_`/`yu-`-Präfixe verwenden. Neue Klassen/IDs sprechend benennen, ohne
  Builder-Präfix.
- Keine Yuuble-Builder-Metadaten/-Marker einführen (`data-uuid`, `data-type`,
  `class="yuuble-engine-node"`, `<!-- Yuuble SDK Config -->`, `data-project-id` usw.). Wird
  Content neu von der Live-Seite übernommen (die diese Marker noch aktiv nutzt), zuerst
  durch dieselbe Cleanup-Logik schicken.
- Seitenübergreifend identische CSS-Blöcke gehören in `resources/css/style.css` bzw.
  `widgets.css`, nicht inline pro Seite.
- Produktbilder, die eindeutig zu einem Produkt gehören, liegen unter
  `assets/img/produkte/{produkt-slug}/{hash}.{ext}`. Echte, mehrfach genutzte Stock-Bilder
  bleiben in `assets/img/` direkt. Datei-Hashes nicht ohne Rücksprache umbenennen.
- **Blade `@context`-Kollision:** Laravel 13 hat eine echte `@context`/`@endcontext`-
  Direktive. Literales `"@context"` in JSON-LD (schema.org) muss als `"@@context"` escaped
  werden, sonst Compile-Fehler. Bei neuen Blade-Direktivnamen generell gegen den
  Content-Bestand prüfen, falls unsicher.
- **Seitenstruktur (Layout-Komponenten):** jede Seite nutzt
  `<x-layout>` (`resources/views/components/layout.blade.php`) als Scaffold. Header/Footer
  sind Single-Source-of-Truth-Components (`components/layout/header.blade.php`,
  `footer.blade.php`). `layout.blade.php` enthält nur echte globale Infrastruktur
  (charset/viewport, favicon, `@vite`-Asset-Ladung) — **kein** Prop-basiertes
  Title/Description/Canonical-Handling. Der komplette seitenspezifische Head-Inhalt
  (hreflang, title, meta description, robots, canonical, OG-/Twitter-Tags, JSON-LD) wird
  unverändert als rohes Markup über einen benannten Slot `<x-slot:head>` durchgereicht —
  bewusst **keine** Props/abgeleiteten Werte, damit jede Seite direkt in ihrer eigenen
  `.blade.php`-Datei editierbar bleibt, genau wie vorher (kein CMS/keine DB dahinter).
- **Vite:** kein Tailwind CSS, keine Bunny-Fonts-Integration (beide aus dem
  Laravel-13-Skeleton entfernt) — bestehendes CSS wird 1:1 migriert, keine
  Utility-Klassen, keine neuen Web-Fonts geplant.

## Kontaktformular & CRM-Anbindung (CAS genesisWorld) — implementiert

Umgesetzt in Phase 4. Steht hier vollständig (nicht nur als Verweis auf `ROADMAP.md`), da
das eine gute Portion CAS-spezifisches Verhalten ist, das man sich sonst erneut erarbeiten
müsste.

- Serverseitiger Versand via Laravel-SMTP-Mailer, POST-Route für `/kontakt`
  (Form-Request-Validierung), Post/Redirect/Get auf **`/danke`** bei Erfolg. Diese Route
  existierte auf der Live-Seite bereits (`https://dormed.de/danke`), war aber bei der
  Quellcode-Übergabe nicht dabei — ursprünglich nur ein Platzhalter zurück auf `/kontakt`
  geplant, bis der Auftraggeber die Original-Seite nachträglich selbst gescrapped und
  nachgeliefert hat. `resources/views/danke.blade.php`, 1:1 migriert wie jede andere Seite
  (Yuuble-Wrapper/`yb-`-Präfixe entfernt, Rest unverändert), `noindex, follow`.
- Firmen-Empfänger-Adresse wird aus `MAIL_FROM_ADDRESS` abgeleitet, **kein** eigener
  `CONTACT_COMPANY_EMAIL`-Env-Var — Sender- und Empfänger-Postfach der Firmen-Mail sind in
  diesem Setup identisch.
- **Reply-To der Firmen-Mail** = die vom Kunden im Formular angegebene Adresse (Mitarbeiter
  können direkt aus dem Postfach antworten). Konsequenz: die Firmen-Mail landet dadurch im
  Konversationsverlauf mit dem Kunden — Template muss präsentabel/professionell aussehen,
  kein Debug-/Rohdaten-Dump.
- Zusätzliche zweite Mail als Eingangsbestätigung an den Kunden.
- Mail-Views unter `resources/views/mail/contact-form/customer.blade.php` und
  `.../company.blade.php`.
- **Zwei getrennte Logs:** `storage/logs/mail.log` (Mail-Funnel, geschrieben von
  `SendInquiryMails`, **jede** Einreichung eine Zeile: `[TT.MM.JJJJ HH:MM:SS] Anfrage von
  {NAME} | Mailversand an {Kunden-E-Mail} {✓|✗}`, deutsches Zeitformat, Status bezieht sich
  nur auf den Kunden-Mailversand) und `storage/logs/api.log` (CRM-Anbindung allgemein,
  geschrieben von `CreateInquiryInCas`/`CasClient`, s. u. — nicht nur Kontaktformular-spezifisch,
  wird bei künftigen weiteren CAS-Anbindungen mitgenutzt). Alles Rahmenwerk-Interne läuft
  unverändert über `storage/logs/laravel.log`.

**CRM-Anbindung (CAS genesisWorld):** jede Einreichung wird zusätzlich als CRM-Datensatz
angelegt, nicht nur gemailt.

- Auth: Basic Auth (`<username>:<password>`, kein Datenbank-Präfix) **plus**
  `X-CAS-PRODUCT-KEY`-Header, über `CAS_GENESIS_WORLD_HOST`/`_USERNAME`/`_PASSWORD`/`_PRODUCT_KEY`.
- Schlanker eigener Service um Laravels `Http`-Facade (`app/Services/Cas/...`), keine neue
  Dependency. Bei mehreren CRM-Integrationen später ggf. Saloon erwägen — nur mit
  Rücksprache.
- Ziel: CRM-Tabelle `Inquiries` (Felder `Name`/`MAIL`/`PHONE`/`ZIP`/`MESSAGE`/
  `COMPANY`/`SPECIALTY`/`CALLBACK_REQUEST`/`CALLBACK_DATE`/`MAIL_STATUS` — exakte Feldnamen
  wie im CRM angelegt, weichen z. T. vom ursprünglichen Entwurf ab. `MAIL_STATUS` existiert im
  CRM, wird aber nicht mehr beschrieben (s. u.). Details/Typen/Längen siehe `ROADMAP.md`
  Phase 4). Die Swagger-Doku der API ist ~95 % korrekt, einzelne Payload-/Response-Schemas
  sind falsch verlinkt oder explizit "undocumented". CAS antwortet auf ein erfolgreiches
  Create mit `201`+leerem Body; die GUID kommt dann aus dem `Location`-Header. GUID-Extraktion
  ist rein informativ (Logging in `api.log`) — nichts hängt mehr funktional davon ab.
- **Zwei unabhängige Jobs, keine Job-Kette:** `CreateInquiryInCas` (legt den CRM-Datensatz an,
  `retryUntil()`, da der CAS-Server nachts ca. eine Stunde rebootet) und `SendInquiryMails`
  (verschickt beide Mails, kennt CAS nicht, eigenes `retryUntil()`) werden beide direkt und
  unabhängig aus `ContactFormController::store()` dispatcht. Ursprünglich als Kette geplant
  (Mail erst nach CAS-Erfolg, GUID-Weitergabe, PUT auf `MAIL_STATUS` als Rückmeldung) — der PUT
  wurde bewusst ersatzlos gestrichen, damit entfällt auch die Abhängigkeit zwischen den Jobs.
- `QUEUE_CONNECTION=database` (ersetzt `sync`) — `jobs`/`failed_jobs`-Migration (in Phase 2
  mangels Bedarf gelöscht) wurde dafür wiederhergestellt. Abarbeitung per Cron
  (`queue:work --stop-when-empty`, alle 1–2 Minuten), bewusst **kein** dauerhafter
  Supervisor-Worker (passt zu Core Rule 5) — später ohne Codeänderung umstellbar.
- Akzeptiertes Restrisiko (nur noch `CreateInquiryInCas` betreffend): geht eine CAS-Antwort
  exakt im Reboot-Moment verloren, kann ein Retry einen doppelten Datensatz erzeugen (kein
  Idempotency-Key-Support in der API). Bewusst nicht weiter abgesichert.

## Consent-Management (Cookies) — implementiert

Umgesetzt in Phase 5.

- `vanilla-cookieconsent` (Orestbida, MIT) als reiner Logik-Layer (Kategorien,
  Consent-State, Re-Prompt bei Policy-Änderung, `onFirstConsent`/`onConsent`/`onChange`-
  Hooks) — kein vorgefertigtes UI/Popup. Alternative Klaro!, falls später viele granular
  togglebare Drittanbieter-Dienste dazukommen.
  - **Wichtig:** der separate, GUI-lose "core"-Build
    (`vanilla-cookieconsent/dist/core/...`) wirft beim `run()` einen Fehler (upstream-Bug,
    ein internes Feld wird dort nie gesetzt). Stattdessen wird der reguläre Full-Build
    importiert (`import * as CookieConsent from 'vanilla-cookieconsent'`) — mit
    `autoShow: false` und `lazyHtmlGeneration` (Default `true`) erzeugt der auch ohne
    `core`-Build kein eigenes Modal-DOM, solange `show()`/`showPreferences()` nie
    aufgerufen werden. Siehe Kommentar in `resources/js/consent.js`.
  - `hideFromBots` bleibt auf Default (`true`) — wichtig für SEO/Crawler, aber Vorsicht bei
    Browser-Tests: Playwright setzt `navigator.webdriver = true`, was denselben Kurzschluss
    auslöst wie ein echter Bot (Consent wird dann nie als gültig gewertet). Für Tests mit
    `page.addInitScript(() => Object.defineProperty(navigator, 'webdriver', { get: () =>
    false }))` gegensteuern, statt `hideFromBots` selbst zu verändern.
- Eigenes, **dezentes** Banner-Markup (`resources/views/components/layout/
  consent-banner.blade.php`), kein vollflächiges Modal, eingebunden über die
  Layout-Komponente. Styling 1:1 aus den bestehenden Design-Tokens abgeleitet (Farben/
  Schriftarten aus `.nav__wrap`/`.footer__wrap` in `resources/css/style.css` — kein neues
  globales `:root`, sondern lokal auf `.consent-banner` gescopte Custom Properties, wie es
  auch die übrigen Komponenten handhaben).
- **Consent-Entscheidung wird ausschließlich in einem eigenen, langlebigen Browser-Cookie
  gespeichert** (`cc_cookie`, Library-Default, 182 Tage), nicht in der Laravel-DB-Session
  (Core Rule 4) — das ist bereits das Standardverhalten der Library, dafür war keine
  zusätzliche eigene Logik nötig. Grund fürs Cookie statt Session: Sessions leben in der
  `sessions`-Tabelle der SQLite-DB, die bei `migrate:fresh` (z. B. automatisiert bei
  Redeploys) komplett geleert wird — ein eigener Cookie bleibt davon unabhängig über
  Deploys hinweg gültig.
- Kategorien-Grundgerüst (`necessary` readOnly/`analytics`/`marketing`) ohne konkrete
  Dienste, bis tatsächlich wieder getrackt wird. `revision: 0` als Basis für künftiges
  Re-Prompting bei Policy-Änderung (hochzählen, sobald sich Kategorien/Zweck ändern).
- SSR-Helper zum serverseitigen Gaten von Drittanbieter-Embeds (z. B. Maps) anhand des
  Consent-Cookies: **zurückgestellt**, da aktuell kein aktives Embed existiert, das davon
  profitieren würde. Nachziehen, sobald eins eingebunden wird.

## Geplante, aber noch nicht umgesetzte Spezifikationen

Bereits mit dem Auftraggeber abgestimmte Entscheidungen für Phasen, die noch nicht gebaut
sind — bei Umsetzung gilt das hier Beschriebene als vereinbarter Startpunkt, nicht als
offene Frage.

### CI/CD & Launch

- **Zwei GitHub-Actions-Workflows für zwei Branches mit unterschiedlicher Deploy-Art:**
  - `main` → Produktiv-Deploy auf den finalen Server. Lint + Tests laufen zuerst, der
    Deploy-Job hängt per `needs:` (Job-Dependency) davon ab und läuft nur bei grünem
    Durchlauf.
  - `claude` → bleibt die Dev-Umgebung (Coolify), aber der Trigger läuft über einen
    GitHub-Actions-Workflow, der bei Push lediglich Coolifys Deploy-Webhook per HTTP
    aufruft (URL/Token als GitHub-Actions-Secret, nicht im Workflow-Code) — statt über
    Coolifys eigene Branch-Watch-Funktion.
- `APP_ENV`/`APP_DEBUG` werden erst beim Umzug auf den finalen Server auf
  Produktionswerte umgestellt (kein Boost-/Dev-Tooling mehr aktiv).
- Google Analytics/Search Console werden **erst unmittelbar nach der DNS-Umstellung**
  angebunden (bestehender Account wird verknüpft, kein Wechsel) — Consent-Management muss
  vorher stehen. Datenlücke von 2–3 Tagen durch DNS-Propagation ist akzeptiert.

## Arbeitsweise

- Bei neuen oder fehlenden Seiten: Text/Specs 1:1 von der Live-Seite übernehmen, niemals
  Fakten erfinden. Fehlen Informationen (z. B. keine SEO-Metadaten auf der Live-Seite
  vorhanden), das offen ansprechen statt zu raten — notfalls selbst verfassen, aber klar
  kennzeichnen, dass es keine 1:1-Übernahme ist.
- Scope-Disziplin: nur umsetzen was explizit verlangt wurde. Keine zusätzliche Abstraktion
  oder Refactoring "weil man gerade dabei ist". Bei Unsicherheit über den Umfang
  nachfragen statt anzunehmen. Alles, was nicht explizit vereinbart ist, vor Umsetzung mit
  dem Auftraggeber (Lukas) abstimmen — keine impliziten Architektur-/Setup-Entscheidungen.
- Ein Commit pro logischer Einheit (z. B. eine neue Produktseite = ein Commit).
  Commit-Messages erklären das Warum, nicht nur das Was.
- **Pull Requests** folgen dem Format in `.github/pull_request_template.md`
  (Summary / Key Changes / Implementation Details) — etabliertes Format seit PR #1.
- Vor Abschluss einer Phase: die jeweiligen DoD-Kriterien aus `ROADMAP.md` gegenprüfen,
  nicht nur "sieht gut aus" annehmen.

## Bekannte offene Punkte (dokumentiert, aktuell nicht in Bearbeitung)

- Impressum, Datenschutzerklärung und AGB (`/impressum`, `/datenschutz`, `/agb`) sind aus der
  Legacy-Übergabe 1:1 übernommen (Originale unter `.old/`). Inhaltlich ungeprüft und teils
  veraltet (z. B. Google Tag Manager statt Rybbit, Consent-Tool, CAS-Anbindung und
  Spam-Score-Dienst nicht erwähnt) — vor dem Livegang rechtlich prüfen/aktualisieren lassen.
- Rund 30 weitere kaputte interne Links, vorbestehend (Tippfehler in Produkt-/
  Leistungs-Slugs, fehlende Blog-Artikel, zwei fehlende Fachgebiets-Seiten unter `/fuer/`).
  Nicht durch die Migration verursacht, aber noch nicht behoben.
- `assets/img/platzhalter-geraet.svg` in `ultraschallgeraete/gebraucht.html` (Gerät
  "DC-40") ist ein bewusster Platzhalter — die Gebrauchtgeräte-Seite war schon bei der
  Vorgänger-Agentur in Arbeit und wird vom Auftraggeber selbst weitergeführt. Nicht
  anfassen ohne explizite Aufforderung.
- Längerfristig relevant, aber noch nicht spezifiziert genug für eine eigene Phase:
  externe System-Integration für Veranstaltungen (Push-Webhook oder Pull/Polling?, nur
  Veranstaltungen oder mittelfristig auch der Produktkatalog?), SonoFinder-Tool (aktuell
  toter Formular-Stub, Entscheidung Alpine.js vs. Livewire hängt von der noch unklaren
  fachlichen Anforderung ab).
