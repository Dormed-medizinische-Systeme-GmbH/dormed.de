# dormed.de

Website der SONORING DORMED med. Systeme GmbH (Ultraschallgeräte-Fachhändler). Laravel 13 /
PHP 8.4, Blade-Views, Vite für CSS/JS. Das Projekt ging aus dem statischen Export eines
Website-Builders („Yuuble") hervor; Details zur Migration: Verzeichnis-READMEs unten.

Ansprechpartner und Auftraggeber: Lukas.

## Leitplanken

1. **URLs sind fix.** Keine Route ändert sich; Google kennt jede Seite exakt so. Neue Seiten
   sind erlaubt, bestehende URLs bleiben.
2. **Keine Business-/Content-Datenbank.** Produktdaten und Formular-Einträge liegen nicht
   dauerhaft in einer DB. Erlaubt ist SQLite als ephemerer Framework-Zustand (Cache,
   Sessions, Queue); die Datei ist gitignored und per `migrate:fresh` neu aufbaubar.
3. **Kein Dauerprozess.** Klassisches PHP-FPM-Hosting; `npm run build` läuft nur beim
   Deploy, die Queue arbeitet per Cron (`schedule:run`).

## Umgebungen

- **Produktiv: dormed.de.** Push auf `main` löst den Deploy aus
  (`.github/workflows/deploy.yml`), sobald `tests.yml` grün ist.
- **Netzwerk in Agenten-Sessions:** Ein 403 auf CONNECT-Ebene ist die Egress-Policy der
  Session, keine Website-Blockade. Melden und um Freigabe bitten.

## Arbeiten

- Setup: `composer run setup`, Entwicklung: `composer run dev`.
- Vor „fertig": `php artisan test --compact` und `vendor/bin/pint --dirty --format agent`.
- PRs folgen `.github/pull_request_template.md`.
- Ein Commit pro logischer Einheit; die Message erklärt das Warum.
- Scope: genau das Verlangte. Architektur-, Setup- und Dependency-Entscheidungen vorab mit
  Lukas klären.

## Dokumentationsbaum

Das Projektwissen steht in `README.md`-Dateien, die einen Baum bilden: Jede README
verlinkt ihre Unterverzeichnisse, und nur diese eine Ebene tiefer. Lies die README eines
Verzeichnisses, bevor du darin arbeitest.

**Regeln**

1. Wissen steht im tiefsten Verzeichnis, das es vollständig umfasst. Querschnittsthemen
   haben ein Zuhause; alle anderen Stellen verlinken dorthin.
2. Eine README entsteht nur, wo es Nicht-Ableitbares gibt: Konvention, Begründung, Falle.
   Was Code, Config oder `composer.json` sagen, wird nicht abgeschrieben.
3. Keine Zeitangaben, Projektphasen oder „geplant"-Aussagen.
4. Ändert ein Commit das Verhalten eines Verzeichnisses, aktualisiert derselbe Commit
   dessen README.
5. `tests/Feature/DocsTreeTest.php` prüft Links und Verlinkung durch den Elternknoten.

**Gerüst einer README**

```markdown
# <Verzeichnis>
<Ein Satz: wofür das Verzeichnis da ist.>

## Inhalt
- [Unterverzeichnis](Unterverzeichnis/README.md) — <Thema>; <wann lesen>.

## Regeln
<Nur Nicht-Ableitbares.>
```

**Verzeichnisse**

_Noch keine Unterverzeichnis-READMEs._
