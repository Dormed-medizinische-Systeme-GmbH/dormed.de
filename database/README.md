# database

SQLite (`database/database.sqlite`, gitignored). Es gibt keinen DB-Server und keine
Zugangsdaten. CAS bleibt die führende Datenquelle; die Datei enthält zwei Arten von Daten.

## Regeln

- **Framework-Zustand, ersetzbar:** `cache`, `sessions`, `jobs`, `failed_jobs`.
- **Spiegel von CAS-Daten, nicht ersetzbar ohne Aufwand:** `calendar_events` (Veranstaltungen,
  [Services/CalendarEvents](../app/Services/CalendarEvents/README.md)) und `used_devices`
  (Gebrauchtgeräte, [Services/UsedDevices](../app/Services/UsedDevices/README.md)). Die
  Sync-Befehle ohne Argument gleichen nur **bereits bekannte** Datensätze ab. Ist die Datei
  leer, füllt sie sich erst wieder durch Webhooks oder durch Angabe der GUIDs
  (`calendar-events:sync {gguid…}`, `used-devices:sync {gguid…}`).
- **Formular-Einträge werden nicht gespeichert.** Eine Anfrage existiert nur als Queue-Job
  und danach als CAS-Datensatz und Mail.
- **`migrate --force` ja, `migrate:fresh` nie auf dem Server.** Der Deploy legt die Datei
  bei Bedarf an (`touch`) und spielt nur neue Migrationen ein; ein Reset löscht den
  Spiegel und alle offenen Jobs.
- **Der Webserver-Benutzer braucht Schreibrechte** auf `database/` und die Datei, nicht
  nur auf die Datei selbst (SQLite legt Journal-Dateien daneben an). Der Deploy setzt sie.
- Migrationen und Factories gehören zusammen: Jedes Modell mit eigener Tabelle hat eine
  Factory (`CalendarEventFactory`, `UsedDeviceFactory`), die Tests nutzen. Der
  `DatabaseSeeder` ist leer.
