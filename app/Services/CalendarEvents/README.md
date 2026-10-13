# app/Services/CalendarEvents

Spiegelt Veranstaltungen aus CAS (Datentyp `APPOINTMENT`) in die Tabelle `calendar_events`.
Quelle der Wahrheit bleibt CAS. Gerendert wird die Arbeitskopie `/veranstaltungen2`
(`CalendarEventController`, `noindex`); die Seite `/veranstaltungen` ist weiterhin
statisches Markup.

## Ablauf

CAS-Webhook `POST /webhooks/cas/veranstaltungen` → Job `SyncCalendarEvent` (unique je GUID,
Bursts für denselben Termin fallen zu einem Lauf zusammen) →
`CalendarEventSynchronizer::sync($guid)`. Der Synchronizer holt den Termin per GUID und
entscheidet:

- **speichern**, wenn er gefunden wird, `GWSTYPE` = `Veranstaltung` ist, die Kategorie
  veröffentlicht ist und `KEYWORD` sowie `START_DT` gefüllt sind;
- **lokal entfernen**, wenn er in CAS gelöscht wurde (`404`) oder eine dieser Bedingungen
  nicht (mehr) zutrifft. Ändert jemand in CAS den Typ, verschwindet der Termin beim
  nächsten Sync;
- **nichts ändern und werfen** bei jedem anderen CAS-Fehler, damit die Queue wiederholt.

Ohne Webhook: `php artisan calendar-events:sync {gguid…}` importiert gezielt; ohne
Argumente gleicht er alle **lokal bekannten** Termine neu ab. Unbekannte Termine kann nur
ein Webhook oder die Angabe der GUID hereinholen.

## Regeln

- **Mapping und Veröffentlichung** steuert `config/calendar_events.php`: Kategorie →
  Typ (`Messe`, `Fachtagung`), optionale Felder (`VERANSTALTUNGS_LINK`, `GWSSTATUS`),
  Statuswerte. Neue Kategorie oder neuer Status gehören in diese Config.
- **Slug nie nachträglich ändern.** Er entsteht einmalig aus `KEYWORD` (bei Kollision mit
  Zähler) und steht auf gedruckten QR-Codes. Umbenennungen in CAS ändern ihn nicht.
- **Ganztägige Termine:** CAS liefert das Ende exklusiv (MEDICA 16.–19.11. kommt als
  Start 16.11., Ende 20.11.); der letzte Tag ist der Tag vor `END_DT`. Ganztags-Werte sind
  Mitternacht UTC, andere Zeiten werden nach `Europe/Berlin` umgerechnet.
- **Status:** Unbekannt oder leer gilt als „Geplant". „Verschoben" behält das alte Datum
  und bleibt gelistet, ohne Countdown. Ein neues Datum setzt den Status zurück auf
  „Geplant"; einen eigenen Status dafür gibt es nicht.
- **Lead-Formular:** Es bleibt nach dem letzten Veranstaltungstag
  `CalendarEvent::LEAD_WINDOW_MONTHS` (3) Monate offen und ist bei „Abgesagt" geschlossen.
  Vergangene Termine bleiben bis zum Löschen in CAS in der Tabelle, damit das Formular
  weiter funktioniert; die Seite zeigt nur laufende und kommende.
- **Anmelde-Link:** Nur `http(s)`-URLs werden übernommen, sonst führt der Button auf
  `/kontakt`.
- Das Modell liefert auch das JSON-LD (`toSchema()`, `schema.org/Event`) der Seite. Bilder
  gibt es nicht: Die CAS-API kann sie nicht sauber ausliefern.
