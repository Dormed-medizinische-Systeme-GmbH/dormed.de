# config

Nur projekteigene Configs sind hier beschrieben; alle übrigen sind Laravel-Standard.

## Regeln

| Datei | Wofür | Gotcha |
|---|---|---|
| `contact.php` | Link-Domains und erlaubte Schriften des Spamschutzes ([app](../app/README.md#spamschutz-supportformguard-rules)) | Subdomains der erlaubten Domains sind automatisch erlaubt. |
| `events.php` | Fallback-Events für das Lead-Formular | Der Schlüssel ist der Slug auf gedruckten QR-Codes und darf sich nie ändern. Eintrag mit `accepts_leads_until`; `calendar_events` hat Vorrang. |
| `calendar_events.php` | CAS-Mapping der Veranstaltungen | Siehe [Services/CalendarEvents](../app/Services/CalendarEvents/README.md). |
| `services.php` | `cas_genesis_world.*` (Host, Zugang, Product-Key, Webhook-Bearer) und `decision_model.*` (Spam-Score) | Beschreibung der CAS-Variablen: [Services/Cas](../app/Services/Cas/README.md). |
| `logging.php` | Kanäle `mail`, `spam`, `api`, `webhook` | Beschreibung der Logs: [app](../app/README.md#logs-storagelogs). |

- **`MAIL_FROM_ADDRESS` ist zugleich das Postfach, das die Firmen- und Lead-Mails
  empfängt.** Eine Änderung wirkt auf beide Seiten.
- **Env-Variablen für Dienste** (CAS, `DECISION_MODEL_URL`/`_KEY`, SMTP) stehen nur in der
  `.env` auf dem Server. Der Deploy erzeugt die `.env` nicht.
- `QUEUE_CONNECTION`, `SESSION_DRIVER` und `CACHE_STORE` sind `database`
  ([database](../database/README.md)).
