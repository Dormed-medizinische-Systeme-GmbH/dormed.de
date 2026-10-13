# resources

Frontend: Blade-Views, CSS, JS und die Sitemap-Inhalte.

## Inhalt

- [views](views/README.md) — Seitenstruktur, Head-Slot, Produktseiten, Neues-Gerät-Checkliste;
  lesen bei jeder Änderung an Seiten.
- [js](js/README.md) — Consent-Logik und Formular-Spamschutz im Browser.

## Regeln

- **CSS:** `css/style.css` (Navigation, Footer, globale Bausteine) und `css/widgets.css`
  (wiederverwendbare Widgets wie `.button_link`, `.badge`, `.faq_accordion`) laden auf jeder
  Seite. Identische CSS-Blöcke mehrerer Seiten gehören hierher, nicht inline in die
  einzelne Seite. Das Consent-Banner bringt sein eigenes `<style>` mit
  (`components/layout/consent-banner.blade.php`); seine Custom Properties sind auf
  `.consent-banner` gescopt, es gibt kein globales `:root`. Es gibt kein Tailwind und
  keine Web-Fonts-Integration.
- **Vite-Eingänge** stehen in `vite.config.js`; `layout.blade.php` lädt `style.css`,
  `widgets.css` und `consent.js`, `form-guard.js` laden nur Seiten mit geschütztem
  Formular (`kontakt`, Event-Lead). Ein neuer Eintrag muss in `vite.config.js` stehen,
  sonst fehlt er im Build.
- **Sitemap:** `sitemap/sitemap.xml` ist ein Index auf `sitemap-system-pages.xml`. Beide
  liefert `routes/web.php` aus (`/sitemap.xml`, `/sitemap-system-pages.xml`), nie als Datei
  aus `public/`. Eine neue indexierbare Seite trägt man von Hand in
  `sitemap-system-pages.xml` ein.
- **Analytics:** `components/layout.blade.php` bindet das Rybbit-Skript als
  `type="text/plain" data-category="analytics"` ein. Die Consent-Library aktiviert es erst
  nach Zustimmung zur Kategorie `analytics` ([js](js/README.md)). Weitere Drittanbieter-
  Skripte folgen demselben Muster, nie als normales `<script src>`.
