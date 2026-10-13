# .github

CI/CD und PR-Konventionen.

## Workflows

- **`tests.yml`** läuft für jeden Pull Request: Pest mit Coverage (PCOV), Mindest-Coverage
  `COVERAGE_MIN`. Der Job `pest` ist als Pflicht-Check für `main` hinterlegt (GitHub →
  Settings → Rules). `scripts/test-report.py` baut aus JUnit-Log und Pest-Ausgabe den
  Testbericht für Job-Zusammenfassung und einen PR-Kommentar, der bei jedem Lauf
  aktualisiert wird (Marker `<!-- test-report -->`).
- **`deploy.yml`** läuft bei Push auf `main` und manuell (`workflow_dispatch`). Der
  Deploy-Job hängt per `needs:` an `tests.yml`, ein roter Test verhindert den Deploy. Nie
  zwei Deploys gleichzeitig, ein laufender Deploy wird nicht abgebrochen.

## Deploy-Ablauf (per SSH, klassischer VPS mit nginx/PHP-FPM)

`git pull --ff-only` (bricht bei Divergenz ab), `composer install --no-dev`, `npm ci`,
`optimize:clear`, `database.sqlite` anlegen, `npm run build`, `migrate --force`,
`optimize`, `chmod -R g+w storage bootstrap/cache database`.

- **Konfiguration** liegt in GitHub: Secret `DEPLOY_SSH_KEY`, Variables `DEPLOY_HOST`,
  `DEPLOY_USER`, `DEPLOY_PATH` und `SSH_KNOWN_HOSTS` (der Deploy läuft mit
  `StrictHostKeyChecking=yes`).
- **Server-Voraussetzungen**, die der Deploy nicht erzeugt:
  - `.env` mit Produktionswerten (CAS, SMTP, `APP_KEY`, …) liegt vorab im Zielverzeichnis.
  - Der Deploy-User hat per eigenem Deploy-Key Lesezugriff auf das GitHub-Repo (nur für
    den Erst-Klon) und node, npm, php, composer, git im PATH seiner SSH-Session.
  - Deploy-User und Webserver-User teilen eine Gruppe, sonst reicht `chmod g+w` nicht.
    Ohne Schreibrechte auf `database/` antwortet jede Seite mit HTTP 500, weil Sessions
    und Cache in SQLite liegen.
  - Cron `* * * * * php artisan schedule:run` ([app](../app/README.md#queue-scheduler-retries)).
- **Kein `migrate:fresh` im Deploy**, nie; der Reset löscht den CAS-Spiegel
  ([database](../database/README.md)). Ein neuer Code-Stand wird ohne Prozess-Neustart
  wirksam, weil kein Dauerprozess läuft.

## Pull Requests

`pull_request_template.md`: Summary, Key Changes, Implementation Details und eine
Checkliste zur Dokumentation ([Dokumentationsbaum](../README.md#dokumentationsbaum)).
