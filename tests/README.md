# tests

Pest, überwiegend Feature-Tests (`Feature/`); `Unit/` enthält nur das Laravel-Beispiel.

## Regeln

- **Ausführen:** `php artisan test --compact`, gefiltert mit `--filter=…`. Die CI führt
  mit Coverage aus und scheitert unter der Mindest-Coverage (`COVERAGE_MIN` in
  `.github/workflows/tests.yml`, [.github](../.github/README.md)).
- **Models für Tests über Factories** (`CalendarEventFactory`, `UsedDeviceFactory`), nicht
  von Hand. Tests mit Datenbankzugriff nutzen `RefreshDatabase`.
- **CAS und Mail werden gefakt**, kein Test spricht mit CAS oder SMTP (`Http::fake`,
  `Mail::fake`, `Queue::fake`).
- **Seiten:** `StaticPagesTest` prüft jede parameterlose GET-Route auf `200`. Es gibt
  daneben gezielte Tests für Rechtsseiten und Footer-Links, Produkt-Markdown, Formulare,
  Spamschutz, Event-Lead und den CAS-Sync.
- **`DocsTreeTest`** hält den README-Baum konsistent: Jeder relative Link muss existieren
  und jede README muss von der nächsten README darüber verlinkt sein
  ([Dokumentationsbaum](../README.md#dokumentationsbaum)).
- **Pro Verhaltensänderung ein Test.** Tests löscht man nicht ohne Rücksprache.
