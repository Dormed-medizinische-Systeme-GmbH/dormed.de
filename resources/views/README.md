# resources/views

Eine Blade-Datei je Seite, Ordnerstruktur = URL-Struktur (`ultraschallgeraete/standgeraete/
esaote-mylab-a70.blade.php` = `/ultraschallgeraete/standgeraete/esaote-mylab-a70`). Die
Seiten gingen 1:1 aus einem statischen Export des früheren Website-Builders („Yuuble")
hervor; dessen Original liegt unverändert in `.old/` und dient als Referenz für Abgleiche.

## Regeln

- **Seitenstruktur:** Jede Seite ist `<x-layout>`. Header, Footer und Consent-Banner sind
  Components (`components/layout/`) und stehen nur dort. `layout.blade.php` enthält nur
  globale Infrastruktur. Der gesamte seitenspezifische Head (hreflang, `title`,
  `description`, `robots`, `canonical`, OG/Twitter, JSON-LD) steht als rohes Markup im
  `<x-slot:head>` der jeweiligen Seite, ohne Props oder abgeleitete Werte, damit jede
  Seite in ihrer Datei vollständig editierbar bleibt.
- **JSON-LD:** `"@context"` als `"@@context"` schreiben (Laravel 13 kennt `@context` als
  Blade-Direktive, sonst Compile-Fehler).
- **Seitenvergleiche** gegen `.old/` über sichtbaren Text, Meta-Tags und geparstes JSON-LD,
  nicht über Pixel-Diffs: `laravel/boost` injiziert in `local`/`debug` ein
  Log-Capture-Script in jede Response, das Rauschen erzeugt (außerhalb davon und mit
  `composer install --no-dev` fehlt es).
- **Keine Builder-Reste:** keine `yb-`/`yb_`/`yu-`-Präfixe und keine Marker wie
  `data-uuid`, `data-type`, `yuuble-engine-node`, `data-project-id`. Content, der neu von
  der Live-Seite oder aus `.old/` kommt, vorher davon bereinigen.
- **Neue Inhalte:** Texte und technische Daten kommen von der Live-Seite oder aus CAS;
  Fehlendes (z. B. SEO-Meta) nicht raten, sondern ansprechen und Selbstverfasstes
  kennzeichnen.
- **Arbeitskopien:** `ultraschallgeraete/gebraucht2` und `veranstaltungen2` werden aus der
  Datenbank gespeist ([UsedDevices](../../app/Services/UsedDevices/README.md),
  [CalendarEvents](../../app/Services/CalendarEvents/README.md)), sind `noindex` und
  stehen neben den statischen Seiten `gebraucht` und `veranstaltungen`.
- **Mail- und Lead-Views:** `mail/contact-form`, `mail/event-lead` (inklusive PDF-Vorlage)
  und `veranstaltungen/lead*` gehören zu den Flows in [app](../../app/README.md).
  Firmenmails haben Reply-To = Kunde und müssen präsentabel bleiben.
- **Rechtstexte** (`impressum`, `datenschutz`, `agb`) stammen unverändert aus der
  Übergabe. Die Datenschutzerklärung nennt Cookiebot und Google Tag Manager, nicht aber
  das tatsächliche Consent-Tool, Rybbit, die CAS-Anbindung und den Spam-Score-Dienst.

## Produktseiten

- **Markdown-Profil:** Liegt neben `produkt.blade.php` eine `produkt.md`, liefert
  `/ultraschallgeraete/…/produkt.md` sie als `text/markdown` aus (Kurzprofil für
  KI-Crawler); ohne Datei antwortet die Route mit 404.
- **Produktbilder:** `public/assets/img/produkte/{produkt-slug}/{hash}.{ext}`
  ([public](../../public/README.md)).

**Neues Gerät: überall eintragen, wo Geräte gelistet sind.**

1. Produktseite (optional `.md`) unter dem passenden Typ-Ordner (`standgeraete`,
   `mobile-geraete`, `handheld`) plus benannte Route in `routes/web.php`.
2. Bilder unter `public/assets/img/produkte/{slug}/`.
3. Listen: Typ-Index (`…/standgeraete/index`), `ultraschallgeraete/index` (Karte,
   JSON-LD-`ItemList`, Hersteller-Modelle), Herstellerseite `hersteller/{hersteller}`
   (Karten im Hero und im Sortiment, `ItemList`, Title/Meta, FAQ-Zahlen),
   `hersteller/index`, Startseite (Hersteller-Karte), Header-Menü.
4. Eintrag in `resources/sitemap/sitemap-system-pages.xml`.
