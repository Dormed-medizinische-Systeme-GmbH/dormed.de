# app

Anwendungslogik: Formulare (Kontakt, Event-Lead), CRM-Anbindung an CAS genesisWorld,
Spiegelung von CAS-Daten (Veranstaltungen, Gebrauchtgeräte) und der Spamschutz.

## Inhalt

- [Services/Cas](Services/Cas/README.md) — CAS-Client, Zugangsdaten, Retry-Verhalten,
  `Inquiries`-Felder; lesen bei jeder CRM-Änderung.
- [Services/CalendarEvents](Services/CalendarEvents/README.md) — Veranstaltungen aus CAS:
  Webhook, Feld-Mapping, Slugs, Lead-Fenster.
- [Services/UsedDevices](Services/UsedDevices/README.md) — Gebrauchtgeräte aus CAS:
  Webhook, Bilder, Normalisierung.

Konfiguration dazu: [config](../config/README.md). Tabellen: [database](../database/README.md).

## Kontaktformular (`POST /kontakt`)

`ContactFormController::store` → zwei **voneinander unabhängige** Jobs:

- `CreateInquiryInCas` legt den CRM-Datensatz an.
- `SendInquiryMails` verschickt Firmen- und Kundenmail und schreibt `mail.log`.

Es gibt keine Job-Kette und keine Rückmeldung an CAS; der Mailversand kennt CAS nicht.

- **Rate-Limit** je IP: 1 pro Minute, 3 pro Tag. Erfolg führt per Post/Redirect/Get auf
  `/danke` (`noindex, follow`).
- **Honeypot:** Löst er aus, leitet der Controller still auf `/danke` weiter und verwirft
  die Anfrage (Eintrag in `laravel.log`).
- **Empfänger der Firmenmail** ist `MAIL_FROM_ADDRESS` (Sender und Postfach sind
  identisch). **Reply-To** ist die Adresse des Kunden, die Firmenmail landet damit im
  Gesprächsverlauf mit ihm und muss präsentabel bleiben.
- **Spam-Score:** `SendInquiryMails` schickt ausschließlich das Freitextfeld `nachricht`
  an einen externen Bewertungsdienst (`services.decision_model`, 8 s Timeout) und hängt
  das Ergebnis (0–1) an die Firmenmail. Jeder Fehler ergibt `null`, die Mails gehen
  trotzdem raus. Der Dienst ist derzeit nicht angebunden: Jeder Aufruf läuft in den
  Timeout, die Firmenmail geht ohne Score raus und der Job braucht entsprechend 8 s länger.

## Event-Lead (`/veranstaltungen/{event}/kontakt`)

Formular für Messen, nur per QR-Code erreichbar: nicht verlinkt, nicht in der Sitemap,
`noindex` per Meta-Tag und `X-Robots-Tag`.

- **Ohne Rate-Limit**, weil auf Messen viele Besucher hinter einer IP sitzen (WLAN,
  Mobilfunk-NAT) und Personal mehrere Leads am selben Gerät erfasst.
- Der Slug wird zuerst gegen `calendar_events` aufgelöst, danach gegen
  [config/events.php](../config/README.md) (Fallback für früh gedruckte QR-Codes).
  Unbekannte Slugs liefern 404. Nach Ablauf des Lead-Fensters zeigt die Seite statt des
  Formulars einen Hinweis mit Link auf `/kontakt`.
- `SendEventLeadMails` verschickt die Firmenmail mit **PDF-Anhang** (dompdf, View
  `mail.event-lead.pdf`; scheitert die PDF-Erzeugung, geht die Mail ohne Anhang raus und
  der Fehler wird geloggt) und die Kundenbestätigung nur, wenn eine E-Mail-Adresse
  angegeben wurde.

## Spamschutz (`Support/FormGuard`, `Rules/`)

Beide Formulare teilen sich `FormGuard`: entfernt unsichtbare Unicode-Zeichen (`\p{Cf}`)
vor der Validierung, lehnt per `NoForeignLinks` fremde Links ab und per
`AllowedScriptsOnly` Schriften außerhalb von Latin/Common/Inherited. Dieselben Muster
liefert `FormGuard::frontendConfig()` an `resources/js/form-guard.js`, damit das Frontend
schon beim Tippen blockt. Ablehnungen landen **ohne Inhalt** in `spam.log`. Erlaubte
Link-Domains und Schriften: [config/contact.php](../config/README.md).

## Queue, Scheduler, Retries

- `QUEUE_CONNECTION=database`; `routes/console.php` plant jede Minute
  `queue:work --stop-when-empty --max-time=50` (ohne Überlappung). Voraussetzung auf dem
  Server ist der Cron-Eintrag `* * * * * php artisan schedule:run`. Es läuft bewusst kein
  Dauer-Worker.
- Alle CAS- und Mail-Jobs wiederholen bis 3 Stunden nach dem Dispatch (`retryUntil`) mit
  Backoff 60/120/300/600 s, weil der CAS-Server nachts etwa eine Stunde neu startet.
- **Akzeptierte Dubletten:** Ein Retry von `SendInquiryMails` kann die Firmenmail doppelt
  senden, ein Retry von `CreateInquiryInCas` einen doppelten CRM-Datensatz erzeugen (die
  API kennt keinen Idempotency-Key). Eine doppelte Meldung ist kleiner als eine verlorene
  Anfrage.
- Endgültig gescheiterte Jobs landen in `failed_jobs`.

## Logs (`storage/logs/`)

| Datei | Inhalt |
|---|---|
| `mail.log` | Eine Zeile je Einreichung: `[TT.MM.JJJJ HH:MM:SS] Anfrage von {Name} \| Mailversand an {E-Mail} ✓/✗` (Lead: `Lead ({Event}) von {Praxis} …`). Der Status betrifft nur den Mailversand. |
| `api.log` | CAS-Anlagen mit **vollständigem Request-Payload** (enthält Name, E-Mail, Telefon, Nachricht) und Response. |
| `webhook.api.log` | Eingehende CAS-Webhooks, CAS-Lesezugriffe (nur Status/Anzahl) und Sync-Ergebnisse. |
| `spam.log` | Abgelehnte Formular-Eingaben: Grund, Feld, Formular, ohne Inhalt. |
| `laravel.log` | Framework, Honeypot-Treffer, gescheiterte Jobs, Spam-Score-Warnungen. |

`api` und `webhook` sind Stacks mit `ignore_exceptions`: Ein nicht beschreibbares Logfile
bricht nie einen Job oder Request ab. Rechte der Logdatei: `0664`.
