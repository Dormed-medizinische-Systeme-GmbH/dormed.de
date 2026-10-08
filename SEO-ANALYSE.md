# SEO-Gesamtanalyse dormed.de

**Stand:** 08.10.2026 · **Basis:** Branch `development/busy-pascal-tyez71` (letzter Commit `dc2b780`)
**Methode:** Statische Analyse aller 81 Routen, Blade-Views, Layout-Komponenten, Sitemaps, `robots.txt`, nginx-/Deploy-Konfiguration sowie automatisierte Prüfung von Meta-Tags, JSON-LD, Überschriften, Bildern und internen Links.

> **Einschränkung:** Die Live-Seite (`https://dormed.de`) war aus der Analyseumgebung nicht erreichbar (Netzwerk-Policy). HTTP-Header, Redirects (www/http → https), Ladezeiten (Core Web Vitals) und Search-Console-Daten sind daher **nicht gemessen**, sondern aus Code und Konfiguration abgeleitet. Die betroffenen Punkte stehen in Abschnitt 10 als „live zu verifizieren“.

---

## 1. Executive Summary

Die SEO-Basis ist **überdurchschnittlich gut**. Fast jede Seite hat einen sauberen Title, eine passende Meta-Description, ein selbstreferenzierendes Canonical, genau eine H1 und umfangreiche strukturierte Daten. Die Core Rule „keine URL ändert sich“ wird eingehalten: alle 81 Sitemap-URLs haben eine Route.

Es gibt aber einige **konkrete Fehler, die Rankings und Rich Results kosten**:

| # | Problem | Schwere | Betroffen |
|---|---|---|---|
| 1 | **Kaputte interne Links** (404), auch im Hauptmenü | 🔴 Hoch | ~30 Ziel-URLs, ~45 Vorkommen in 20+ Views |
| 2 | **`"price": "0"` im Offer-Schema** („Preis auf Anfrage“ als 0 €) | 🔴 Hoch | 27 Produktseiten + 2 Leistungsseiten |
| 3 | **7 indexierbare Seiten ohne Meta-Description und mit Kurz-Title**, davon 3 Produktseiten ohne JSON-LD | 🔴 Hoch | MyLab C25, MyLab X1 Go, MU7, Sono Finder, Digitale Sonothek, Wirtschaftlichkeit (+ Danke) |
| 4 | **Uneinheitliche Telefonnummern** (NAP), teils falsche Click-to-Call-Nummern | 🔴 Hoch (Local SEO) | Footer (global), Kontakt, Danke, Dortmund, Impressum, Datenschutz, Kunden-Mail |
| 5 | **Kein `og:image` / `twitter:image`** auf keiner Seite | 🟠 Mittel | alle 81 Seiten |
| 6 | **noindex-Seiten in der Sitemap** | 🟠 Mittel | `/agb`, `/datenschutz`, `/impressum`, `/danke` |
| 7 | **Self-serving `AggregateRating`** auf LocalBusiness/Organization | 🟠 Mittel (Richtlinien-Risiko) | Startseite, Standorte |
| 8 | **Trailing-Slash-Duplikate** (kein 301, 199 interne Links mit `/` am Ende) | 🟠 Mittel | 51 Views |
| 9 | **Performance:** bis 118 KB Inline-CSS pro Seite, keine Bildmaße, kein WebP/AVIF, keine Kompression/Caching-Header in nginx | 🟠 Mittel | alle Seiten |
| 10 | Kein eigenes 404-Template, statische Sitemap mit veralteten `lastmod` | 🟡 Niedrig | global |

**Gesamtbewertung: 7/10.** Die Punkte 1–4 lassen sich in 1–2 Arbeitstagen beheben und sind der größte Hebel.

---

## 2. Technische Basis

### 2.1 Architektur
- Laravel 13, Blade, Vite. Seiten sind über `Route::view()` registriert (`routes/web.php`).
- Gemeinsames Layout: `resources/views/components/layout.blade.php` (Header, Footer, Consent-Banner). SEO-Tags stehen pro Seite im `<x-slot:head>`, nicht zentral. Das ist der Grund für die Inkonsistenzen (fehlende Descriptions, kein `og:image`): jede Seite pflegt ihren `<head>` selbst.
- `html lang="de-DE"` ✅, `meta viewport` ✅.

### 2.2 Befunde im Layout (`components/layout.blade.php`)
| Befund | Bewertung |
|---|---|
| `<meta charset>` und `<meta viewport>` stehen **doppelt** | Harmlos, aber unsauber. Je einmal reicht. |
| Leeres `<style id="yuuble-theme-style">:root{}</style>` | Überbleibsel aus Yuuble, entfernen. |
| Rybbit-Analytics (`rybbit.everding.it`) wird **unabhängig vom Consent** geladen | Kein SEO-Thema, aber Datenschutz-relevant. Rybbit ist cookielos, das muss in der Datenschutzerklärung stehen. Laut ROADMAP Phase 6 sollte Analytics erst nach Consent aktiv sein; das ist hier nicht so umgesetzt. |
| Favicon als PNG über `/assets/img/<uuid>.png`, zusätzlich `public/favicon.ico` | OK. Optional: `sizes` angeben, 180×180 Apple-Touch-Icon. |
| Kein zentraler Fallback für Description, `og:image` und Organization-Schema | Empfehlung: Defaults ins Layout (Abschnitt 11). |

### 2.3 robots.txt (`public/robots.txt`)
- ✅ `Sitemap: https://dormed.de/sitemap.xml` ist gesetzt.
- ⚠️ Generische Shop-Vorlage: `/admin`, `/login`, `/account`, `/checkout`, `/cart`, `/search` gibt es nicht. Das schadet nicht, ist aber Ballast.
- ⚠️ `Allow: /public/` ist sinnlos, weil `public/` der Webroot ist.
- ⚠️ Der zweite Block für Googlebot, Bingbot usw. wiederholt nur den ersten. Wichtig: Diese Bots lesen **nur** ihren eigenen Block, die Regeln `Disallow: /*?*utm_*` und `/*?*sort*` gelten für sie also nicht. Wenn das gewollt ist, bitte bewusst dokumentieren, sonst den zweiten Block entfernen.
- Kein Eintrag für KI-Crawler (GPTBot, ClaudeBot, PerplexityBot, Google-Extended). Jetzt ist ein guter Zeitpunkt, das bewusst zu entscheiden, zumal es für KI-Crawler schon `.md`-Profile gibt (Abschnitt 8).

### 2.4 Sitemaps
- `/sitemap.xml` ist ein Sitemap-Index und verweist auf `/sitemap-system-pages.xml` mit **81 URLs**. Beide laufen über Routen (Core Rule 2) ✅.
- ✅ Jede Sitemap-URL hat eine Route, es gibt keine verwaisten Einträge.
- ✅ `/ultraschallgeraete/gebraucht2` (noindex, Testseite) steht korrekt **nicht** in der Sitemap.
- ❌ **noindex-Seiten in der Sitemap:** `/agb`, `/datenschutz`, `/impressum`, `/danke`. Das sind widersprüchliche Signale („bitte crawlen“ und „nicht indexieren“), die in der Search Console als Fehler auftauchen. → Aus der Sitemap entfernen.
- ⚠️ `lastmod` ist statisch eingefroren (April–Juni 2026) und spiegelt Änderungen seit der Laravel-Migration nicht wider. `changefreq` und `priority` ignoriert Google. → Sitemap dynamisch aus dem Routen-Bestand erzeugen, `lastmod` aus `filemtime()` der View (Abschnitt 11).

### 2.5 Server / nginx (`nginx.template.conf`)
| Thema | Befund |
|---|---|
| Kompression | ❌ Kein `gzip on;` (und kein Brotli). Das HTML ist teils 200–280 KB groß, Kompression würde die Übertragungsmenge um 70–85 % senken. *(Ob Coolify oder ein Proxy davor komprimiert, ist live zu prüfen.)* |
| Caching statischer Assets | ❌ Keine `expires`/`Cache-Control` für `/assets/` und `/build/`. Vite-Assets sind gehasht und können `immutable` mit 1 Jahr bekommen. |
| Trailing Slash | ❌ `try_files $uri $uri/ /index.php` plus Laravel-Routing: `/kontakt/` liefert **200** statt 301 auf `/kontakt`. Das Canonical federt den Duplicate Content ab, ein 301 ist aber sauberer. |
| www → non-www / http → https | Nicht in der App-Konfiguration. Wird vermutlich vom Proxy (Coolify/Traefik) gemacht, **live zu verifizieren**. |
| HSTS | Nicht gesetzt. |
| 404 | `error_page 404 /index.php`, Laravel rendert die **Standard-404 ohne Navigation** (kein `resources/views/errors/404.blade.php`). Nutzer und Crawler landen in einer Sackgasse. |

---

## 3. On-Page: Titles & Meta-Descriptions

### 3.1 Übersicht
- **74 von 81** Seiten haben einen ausformulierten Title mit Keyword und Marke (`… | DORMED`) und eine Description von 137–210 Zeichen ✅.
- **Keine doppelten Titles oder Descriptions** unter den indexierbaren Seiten ✅ (einziges Duplikat: `gebraucht` / `gebraucht2`, wobei `gebraucht2` noindex ist und per Canonical auf `gebraucht` zeigt, also OK).

### 3.2 ❌ Seiten mit fehlender Description und Platzhalter-Title (alle `index, follow`)

| URL | Title (aktuell) | Description | JSON-LD |
|---|---|---|---|
| `/ultraschallgeraete/mobile-geraete/esaote-mylab-c25` | „Esaote C25“ (10 Zeichen) | ❌ fehlt | ❌ fehlt |
| `/ultraschallgeraete/mobile-geraete/esaote-mylab-x1-go` | „Esaote MyLab X1 Go“ | ❌ fehlt | ❌ fehlt |
| `/ultraschallgeraete/mobile-geraete/mindray-mu7` | „Mindray MU7“ | ❌ fehlt | ❌ fehlt |
| `/ultraschallgeraete/sono-finder` | „Sono Finder“ | ❌ fehlt | ❌ fehlt |
| `/standorte/digitale-sonothek` | „Digitale Sonothek“ | ❌ fehlt | ❌ fehlt |
| `/fuer/kardiologie/wirtschaftlichkeit` | „Wirtschaftlichkeit“ | ❌ fehlt | ❌ fehlt |
| `/danke` (noindex) | „Danke“ | ❌ fehlt | – (unkritisch) |

→ Die drei Produktseiten fallen gegenüber den übrigen Produktseiten deutlich ab: Ohne Description, Product-Schema und Kauf-Keyword im Title („… kaufen“) haben sie schlechtere Chancen auf Rankings und Snippets als die 27 vollständigen Produktseiten. Vorlage: z. B. `mindray-dp-10.blade.php`.

### 3.3 Hinweise zur Länge
- Titles über ca. 60 Zeichen werden in der SERP abgeschnitten: `/leistungen/wartung-reparatur` (74), `/veranstaltungen` (73), `/karriere` (71), `/leistungen/lieferung` (69), `/hersteller/chison` (68), `/kontakt` (68). Das ist kein Fehler, aber prüfenswert, ob das Wichtigste vorne steht.
- Descriptions über ca. 160 Zeichen werden gekürzt: `/ultraschallgeraete/standgeraete/mindray-nuewa-i10` (210), `/hersteller/chison` (192), `/ultraschallgeraete/mobile-geraete/chison-sonoair-70` (194).

---

## 4. Canonical, Robots, hreflang

| Prüfung | Ergebnis |
|---|---|
| Canonical vorhanden und selbstreferenzierend | ✅ 80/80 indexierbare Seiten. `gebraucht2` → `gebraucht` (gewollt). |
| Canonical-Format | ✅ einheitlich ohne Trailing Slash, absolut, `https://dormed.de`. |
| `meta robots` | ✅ überall gesetzt. `noindex, follow` für AGB, Datenschutz, Impressum, Danke; `noindex, nofollow` für `gebraucht2`. |
| Impressum auf noindex | ⚠️ Vertretbar, aber unüblich. Ein indexiertes Impressum ist ein schwaches Vertrauenssignal (E-E-A-T, Brand-Suchen). Empfehlung: Impressum auf `index, follow`, Datenschutz und AGB können noindex bleiben. |
| hreflang | ✅ technisch korrekt (`de-DE` + `x-default`, selbstreferenzierend). Bei einer einsprachigen Seite **ohne Mehrwert**, kann aber bleiben. |

---

## 5. Überschriften & Content

- ✅ **Genau eine H1** auf jeder indexierbaren Seite. Die Rechtsseiten AGB, Datenschutz und Impressum haben **keine H1** (noindex, unkritisch, für die Barrierefreiheit aber trotzdem ergänzen).
- ⚠️ **Produkt-H1s sind sehr kurz** und ohne Gattungsbegriff, z. B. `<h1>Mindray<span>DP-10</span></h1>`. Das ist kein Fehler, weil der Title den Kontext liefert. Ein visuell unauffälliger Zusatz („Mindray DP-10 – mobiles Ultraschallgerät“) würde die Relevanz für generische Suchanfragen aber stärken. *(Core Rule 6 beachten: Textänderung nur mit Freigabe.)*
- **Content-Tiefe:**
  - Fachbereichs-Ratgeber (`/fuer/…`) mit **2.500–3.900 Wörtern**: sehr stark und das wichtigste SEO-Asset der Seite.
  - Produktseiten mit 750–1.200 Wörtern: gut.
  - Hub-Seiten `/fuer`, `/hersteller`, `/leistungen`, `/ueber` mit **150–180 Wörtern**: dünn. Als reine Verteilerseiten OK, ein kurzer einleitender Absatz mit Keyword würde helfen.
  - ❌ `/blog` (114 Wörter, `index, follow`, in der Sitemap): Es gibt **keine Blog-Artikel**, aber 15+ interne Links zeigen auf `/blog/kardiologie/...` (siehe 7.1). Entweder Inhalte erstellen oder `/blog` bis dahin auf noindex setzen.
  - `/ultraschallgeraete/sono-finder` (134 Wörter, Formular-Stub ohne Funktion, laut ROADMAP Phase 7): Thin Content, aber indexiert und im Footer verlinkt.

---

## 6. Strukturierte Daten (JSON-LD)

### 6.1 Abdeckung ✅
Sehr umfangreich und überwiegend sauber über `@graph` und `@id` verknüpft:

| Seitentyp | Schema-Typen |
|---|---|
| Startseite | Organization, LocalBusiness ×4, WebSite, WebPage, BreadcrumbList, AggregateRating |
| Produktseiten (27 von 30) | Product, Brand, Offer, PriceSpecification, PropertyValue, BreadcrumbList |
| Kategorie-Seiten | CollectionPage, ItemList, FAQPage, BreadcrumbList |
| Fachbereiche | WebPage, FAQPage, ItemList, BreadcrumbList |
| Leistungen | Service, HowTo, FAQPage, Offer/OfferCatalog |
| Standorte | LocalBusiness, GeoCoordinates, OpeningHoursSpecification, AggregateRating |
| Karriere | JobPosting |
| Veranstaltungen | Event, Place |
| Hersteller | AboutPage, Brand, ItemList |

`"@@context"` in Blade wird korrekt zu `"@context"` gerendert ✅. Die Organisation wird über `https://dormed.de/#organization` 103-mal referenziert, eine konsistente Entität ✅.

### 6.2 ❌ Offers mit `"price": "0"` (27 Produktseiten + `/leistungen/beratung`, `/leistungen/lieferung`)
```json
"priceSpecification": { "price": "0", "priceCurrency": "EUR", "description": "Preis auf Anfrage" }
```
Google liest das als **„kostet 0 €“**. Folgen: Search-Console-Warnungen bei Händlereinträgen und Produkt-Snippets, im schlimmsten Fall ein Snippet „0,00 €“ oder eine manuelle Maßnahme wegen irreführender Daten.
**Fix:** Bei „Preis auf Anfrage“ das `offers`-Objekt **komplett weglassen** (Product-Snippets funktionieren dann über Review/Rating nicht, das Schema bleibt aber valide und ehrlich) oder einen echten Mindest- bzw. Listenpreis angeben.

### 6.3 ⚠️ Self-serving Reviews (`AggregateRating`)
`AggregateRating` steht direkt an LocalBusiness/Organization (Startseite: 98 bzw. 66 Bewertungen, Standorte: 26 / 5 / 1). Google zeigt seit 2019 **keine Sterne für selbstbezogene Bewertungen** bei LocalBusiness/Organization an. Werden die Daten aus Google-Maps-Bewertungen kopiert, verstößt das zusätzlich gegen die Richtlinien („nicht auf der eigenen Seite gesammelt“).
**Fix:** `aggregateRating` entfernen. Die Bewertungen wirken über das Google-Unternehmensprofil.

### 6.4 ⚠️ Rich-Result-Typen mit eingeschränktem Nutzen
- **FAQPage:** Rich Results zeigt Google seit 2023 nur noch für autoritative Gesundheits- und Behördenseiten. Das Markup schadet nicht und hilft KI-Suchsystemen, sichtbare Ausklapper in der SERP sind aber nicht zu erwarten.
- **HowTo:** Rich Results sind seit 2023 eingestellt. Kann bleiben, bringt aber nichts.

### 6.5 Weitere Lücken
- Product-Schema fehlt auf MyLab C25, MyLab X1 Go und MU7 (siehe 3.2).
- Keine Schema-Daten auf `/fuer/allgemeinmedizin/sonographie`, `/fuer/kardiologie/cw-doppler`, `/fuer/kardiologie/pw-doppler` und `/fuer/kardiologie/wirtschaftlichkeit`, obwohl die Schwesterseiten FAQPage haben (Inkonsistenz).
- `/ultraschallgeraete/gebraucht`: Hier wäre `ItemList` mit `Product` und `itemCondition: UsedCondition` für die konkreten Gebrauchtgeräte sinnvoll, sobald `gebraucht2` (dynamisch per CAS-Webhook) live geht.
- `manufacturer.logo` für Mindray zeigt auf `https://www.mindray.com/favicon.ico`. Das ist ungeeignet, weglassen.
- Empfehlung: JSON-LD nach jeder Änderung im **Rich Results Test** bzw. Schema Validator prüfen und per Feature-Test absichern (Abschnitt 11).

---

## 7. Interne Verlinkung

### 7.1 ❌ Kaputte interne Links (404)
Gefunden: **~30 nicht existierende Ziel-URLs**, darunter ein Link im **globalen Header-Menü** (also auf allen 81 Seiten):

| Defekter Link | Vermutlich gemeint | Fundstellen |
|---|---|---|
| `/ultraschallgeraete/mobile-geraete/mindray-te-7` | `…/mindray-te-7-ace` | **`components/layout/header.blade.php`** (global!) |
| `/ultraschallgeraete/mobile-geraete/mindray-te7`, `…/mindray-te7-ace` | `…/mindray-te-7-ace` | te-air-i3m, te-9, te-5 |
| `/ultraschallgeraete/mobile-geraete/mindray-te9` | `…/mindray-te-9` | mx7, te-7-ace |
| `/ultraschallgeraete/standgeraete/mindray-conson-n6` | `…/mindray-consona-n6` | kardiologie/pw-doppler |
| `/ultraschallgeraete/mindray-consona-n6`, `/ultraschallgeraete/esaote-mylab-a70` | `/ultraschallgeraete/standgeraete/…` | kardiologie/cw-doppler |
| `/ultraschallgeraete/handgeraete`, `/ultraschallgeraete/handhelds` | `/ultraschallgeraete/handheld` | te-air-e5m, te-air-i3m, ueber/sonoring |
| `/ultraschallgeraete/handheld/te-air-e5m` | `…/handheld/mindray-te-air-e5m` | ultraschallgeraete/index |
| `/ultraschallgeraete/tragbare-geraete` | `/ultraschallgeraete/mobile-geraete` | chison-sonoair-70 |
| `/ultraschallgeraet-kaufen` | ? (`/ultraschallgeraete`) | ultraschallgeraete/index |
| `/leistungen/lieferung-installation`, `/lieferung-installation` | `/leistungen/lieferung` | C25, X1 Go, MU7, DP-10 (6×) |
| `/leistungen/beratung-reparatur` | `/leistungen/beratung` oder `/wartung-reparatur` | kardiologie/pw-doppler |
| `/finanzierung`, `/netzwerkanbindung`, `/schulung-einweisung`, `/wartung-reparatur` | `/leistungen/…` | mindray-dp-10 |
| `/sono-finder` | `/ultraschallgeraete/sono-finder` | kardiologie/pw-doppler |
| `/fuer/kardiologie/farbduplex` | `/fuer/kardiologie/farbduplexsonographie` | kardiologie/echokardiographie |
| `/fuer/kardiologie/gewebedoppler` | existiert nicht | kardiologie/echokardiographie |
| `/fuer/gefaessmedizin`, `/fuer/innere-medizin` | existieren nicht | C25, MU7 |
| `/blog/kardiologie`, `/blog/kardiologie/farbduplex(/)`, `/blog/kardiologie/gewebedoppler`, `/blog/kardiologie/pw-doppler`, `/blog/sonographie` | Blog existiert nicht → auf `/fuer/kardiologie/…` umbiegen | allgemeinmedizin/sonographie, kardiologie/cw-doppler, kardiologie/pw-doppler (zusammen 10+×) |

**Auswirkung:** verschwendetes Crawl-Budget, verlorener Linkjuice, schlechtere Nutzererfahrung. Der Header-Link ist am dringendsten.
**Fix:** Links korrigieren *(Core Rule 6 erlaubt das nur mit Rücksprache, weil sich das ausgelieferte HTML ändert; hier klar im Interesse des Projekts)*. Optional zusätzlich 301-Redirects für die häufigsten Fehl-URLs, falls sie schon extern verlinkt oder indexiert sind.

### 7.2 ⚠️ Trailing-Slash-Links
**199 interne Links in 51 Views** enden auf `/` (z. B. `/ultraschallgeraete/mobile-geraete/` 26×, im Header `/hersteller/mindray/`, `/ultraschallgeraete/mobile-geraete/chison-sonoair-70/`). Sie liefern 200 (Duplikat) statt der kanonischen URL.
**Fix:** global auf die Variante ohne Slash umstellen **und** 301 Slash → ohne Slash auf Server- oder Middleware-Ebene einrichten.

### 7.3 Positiv ✅
- Mega-Menü verlinkt alle Produkt-, Leistungs- und Standortseiten. Der Footer deckt Fachbereiche, Hersteller und Rechtliches ab. Die Klicktiefe ist ≤ 2 für alle wichtigen Seiten.
- Produktseiten haben einen Block „Passende Alternativen“ und „Rund ums Gerät“ (Cross-Linking).
- Fachbereichs-Ratgeber verlinken auf externe Autoritätsquellen (DGK-Leitlinien, KBV, Ärzteblatt, Wikipedia). Das ist gut für E-E-A-T.
- `/ultraschallgeraete/gebraucht2` wird nirgends verlinkt (nur der Controller kennt die Seite) ✅.

---

## 8. KI-Suche / Generative Engine Optimization (GEO)

- ✅ **Markdown-Kurzprofile** für Produkte: `/ultraschallgeraete/{…}.md` (Route `ultraschallgeraete.markdown`, `text/markdown`). Gute Basis für LLM-Crawler.
- ❌ **Kein Profil** für Mindray Nuewa i10, MU7, Esaote MyLab C25 und MyLab X1 Go.
- ❌ Die `.md`-Dateien sind **nicht auffindbar**: kein `<link rel="alternate" type="text/markdown" href="….md">` im Head, kein Eintrag in der Sitemap, keine `/llms.txt`.
  **Fix:** `/llms.txt` als Route anlegen (Unternehmensprofil, Standorte, Links auf alle `.md`-Profile und die Kernseiten) und pro Produktseite einen `rel="alternate"`-Link setzen.
- ⚠️ `robots.txt` enthält keine Regel für KI-Crawler. Bewusst entscheiden: zulassen (Sichtbarkeit in ChatGPT, Perplexity, Google AI Overviews) oder sperren.
- ✅ Die FAQ-Blöcke und sehr ausführlichen Fachratgeber sind ideal, um in KI-Antworten zitiert zu werden.

---

## 9. Local SEO

### 9.1 Positiv ✅
- Vier Standortseiten (Dortmund/Unna, Düsseldorf/Ratingen, Hamburg/Seevetal, Kiel) mit LocalBusiness-Schema, Geo-Koordinaten, Öffnungszeiten, Karten-Link und Team.
- `sameAs` verweist auf Google-Maps-Profile, Facebook und LinkedIn.

### 9.2 ❌ NAP-Inkonsistenz Telefonnummer
Zentrale Nummer laut JSON-LD (18×) und Hauptlinks (10×): **`+49 2301 188600`**. Daneben gibt es:

| `tel:`-Link | Problem | Fundstellen |
|---|---|---|
| `+4923011886000` | eine `0` zu viel (angezeigt: „02301 / 188-600“) | **Footer (global)**, Impressum, Datenschutz, Digitale Sonothek |
| `023011886000` | eine `0` zu viel | Kontakt (2×), Standort Dortmund |
| `+4923118860` | falsche Vorwahl (0231 statt 02301), Ziffern fehlen | Kontakt, Danke, **Kunden-Bestätigungsmail** |
| `02303188600` | falsche Vorwahl (02303 = Unna) | Standort Dortmund (Team) |

Falls `…1886000` eine gültige Durchwahl ist, wäre das nur eine Inkonsistenz. Die Varianten `+4923118860` und `02303188600` sind aber **sicher falsch**, Anrufe gehen ins Leere oder zu Fremden. Für Local SEO zählt Konsistenz von Name, Adresse und Telefon (NAP) zwischen Website, Google-Unternehmensprofil und Verzeichnissen.
**Fix:** Eine Nummer festlegen, zentral in `config/` ablegen und in allen Views, Mails und im Schema verwenden.

### 9.3 Weitere Punkte
- Der Footer nutzt `itemprop="telephone"` (Microdata) zusätzlich zum JSON-LD. Das kann bleiben, sollte aber dieselbe Nummer tragen.
- `/standorte/digitale-sonothek`: Title, Description und Schema fehlen (siehe 3.2).
- Empfehlung: Prüfen, dass NAP im Google-Unternehmensprofil exakt der Website entspricht.

---

## 10. Performance & Core Web Vitals (aus dem Code abgeleitet)

| Thema | Befund | Auswirkung |
|---|---|---|
| **Inline-CSS** | Pro Seite bis **118 KB** `<style>` (Startseite), Fachratgeber ca. 72 KB, Median 29 KB. HTML bis 284 KB. | Wird bei jedem Seitenaufruf neu übertragen und ist nicht cachebar. Höhere TTFB-Last und FCP/LCP. ROADMAP Phase 1.4 („Globale Stylings zentralisieren“) adressiert das bereits. |
| **Inline-JS** | bis 34 KB pro Seite | ähnlich |
| **Bildmaße** | ❌ Fast **kein `<img>` hat `width`/`height`** | Layout-Verschiebungen, also schlechterer **CLS** |
| **Bildformate** | 254 JPG, 69 PNG, 0 WebP/AVIF (20 MB gesamt) | Mit WebP/AVIF und `srcset` ließen sich 30–60 % einsparen. Das größte Bild hat 398 KB (PNG). |
| **Lazy Loading** | gemischt: 58× `loading="eager"`, Galerie-Thumbnails ohne `loading` | Thumbnails unterhalb des sichtbaren Bereichs auf `lazy` stellen |
| **LCP-Bild** | kein `fetchpriority="high"`, kein `preload` | Hero-Bild bekommt keine Priorität |
| **Webfonts** | `Space Grotesk` und `JetBrains Mono` werden 2.500× referenziert, aber **nirgends geladen** (kein `@font-face`, kein Google Fonts) | Fällt auf Systemschriften zurück. Kein Performanceproblem, aber das Design weicht ab, wenn die Fonts lokal fehlen. Klären, ob gewollt. |
| **Kompression/Caching** | nicht in nginx konfiguriert (siehe 2.5) | Größter Hebel für die Ladezeit, **live verifizieren** |
| **Third Party** | nur Rybbit (defer) | ✅ schlank |

**Live zu verifizieren** (PageSpeed Insights, Search Console → Core Web Vitals): LCP, CLS, INP auf Mobilgeräten für Startseite, eine Produktseite und einen Fachratgeber. Außerdem Redirects www/http, Content-Encoding und Cache-Header.

---

## 11. Bilder & Barrierefreiheit (SEO-relevant)

- ✅ Haupt- und Kartenbilder haben beschreibende `alt`-Texte (z. B. „Mindray DP-10 kompaktes Schwarz-Weiß Ultraschallsystem“).
- ⚠️ **Galerie-Thumbnails mit `alt=""`**: 4 pro Produktseite, insgesamt ca. 110 Bilder. Weil die Thumbnails dasselbe Bild wie das Hauptbild zeigen, ist `alt=""` (dekorativ) vertretbar. Echte Galeriebilder (Detailansichten) sollten für die Bildersuche aber ein Alt-Attribut bekommen, z. B. „Mindray DP-10 Bedienfeld“.
- ⚠️ Weitere Bilder ohne Alt: `/fuer/kardiologie` (3), `/standorte/digitale-sonothek` (1), `/ueber/sonoring` (1), `/ultraschallgeraete` (4).
- ⚠️ Dateinamen sind UUIDs (`3c5d9dd2-….png`). Sprechende Namen (`mindray-dp-10-front.png`) helfen leicht in der Bildersuche. Das ändert aber Asset-URLs, also nur bei neuen Bildern umsetzen.
- ❌ `/ultraschallgeraete/gebraucht`: Platzhalterbild `platzhalter-geraet.svg` (laut ROADMAP bekannt) muss durch ein echtes Foto ersetzt werden.
- ❌ Kein `og:image`: Beim Teilen auf LinkedIn, Facebook, WhatsApp oder Teams erscheint **kein Vorschaubild**, obwohl `twitter:card=summary_large_image` gesetzt ist. Für einen B2B-Vertrieb mit LinkedIn-Präsenz ist das ein sichtbarer Mangel.

---

## 12. Testabdeckung SEO

- Es gibt Feature-Tests für statische Seiten, Rechtsseiten, Markdown-Profile, Kontaktformular und Gebrauchtgeräte.
- ❌ **Keine automatisierten SEO-Regressionstests.** Bei 81 Seiten mit handgepflegtem `<head>` entstehen Fehler wie in 3.2, 6.2 und 7.1 genau deshalb.
- **Empfehlung** (Pest, ein Datensatz über alle Routen):
  - jede indexierbare Seite: Title 30–65 Zeichen, Description 70–165 Zeichen, Canonical = eigene URL, genau eine H1, `og:image` vorhanden
  - JSON-LD ist valides JSON und enthält kein `"price": "0"`
  - alle internen `href` treffen eine registrierte Route (das hätte alle Fehler aus 7.1 gefunden)
  - noindex-Seiten stehen nicht in der Sitemap

---

## 13. Maßnahmenplan (priorisiert)

### Sofort (Quick Wins, ca. 1–2 Tage)
1. **Kaputte interne Links korrigieren**, zuerst den Header-Link `mindray-te-7` → `mindray-te-7-ace`, dann die Liste aus 7.1.
2. **`"price": "0"` entfernen** auf 27 Produkt- und 2 Leistungsseiten (Offer weglassen oder echten Preis angeben).
3. **Telefonnummer vereinheitlichen** (Footer, Kontakt, Danke, Dortmund, Impressum, Datenschutz, Kundenmail), zentral als Config-Wert.
4. **Head vervollständigen** für MyLab C25, MyLab X1 Go, MU7, Sono Finder, Digitale Sonothek, Wirtschaftlichkeit: Title, Description, OG und JSON-LD (Product, BreadcrumbList).
5. **noindex-Seiten aus der Sitemap entfernen** (`/agb`, `/datenschutz`, `/impressum`, `/danke`).
6. **`aggregateRating` entfernen** von LocalBusiness/Organization.

### Kurzfristig (1–2 Wochen)
7. **`og:image`/`twitter:image`** global: Default-Bild im Layout plus Produktbild auf Produktseiten.
8. **301-Redirect Trailing Slash** (Middleware oder nginx) und interne Links ohne Slash.
9. **Eigene 404-Seite** mit Layout, Suche bzw. Hauptkategorien und Kontakt.
10. **nginx:** `gzip on` (plus Brotli, falls möglich), `Cache-Control` für `/assets/` und `/build/`, HSTS.
11. **`width`/`height` an alle `<img>`**, `fetchpriority="high"` am Hero-Bild, Thumbnails `lazy`.
12. **SEO-Regressionstests** (Abschnitt 12).

### Mittelfristig
13. **Dynamische Sitemap** aus den Routen, `lastmod` per `filemtime()` (Core Rule 2 ist dafür schon vorbereitet).
14. **SEO-Daten zentralisieren:** eine `<x-seo>`-Komponente oder ein Config-Array pro Route (title, description, image, schema). Das beseitigt die Ursache der Inkonsistenzen.
15. **Inline-CSS auslagern** (ROADMAP 1.4) und Bilder nach WebP/AVIF mit `srcset` umstellen.
16. **`/llms.txt`** plus `rel="alternate" type="text/markdown"`, fehlende `.md`-Profile ergänzen, KI-Crawler-Policy in `robots.txt`.
17. **Blog-Strategie:** entweder echte Artikel (die `/blog/kardiologie/...`-Links zeigen, dass das geplant war) oder `/blog` vorerst auf noindex setzen.
18. **`robots.txt` aufräumen** (Shop-Pfade raus, Bot-Block vereinheitlichen).
19. **Impressum indexierbar** machen (E-E-A-T).
20. Rybbit erst nach Consent laden oder als cookieloses Tracking in der Datenschutzerklärung beschreiben.

### Nach Go-Live (ROADMAP Phase 6)
21. Search Console: Sitemap einreichen, Bericht „Seitenindexierung“ und „Verbesserungen“ (Produkt-Snippets, Breadcrumbs) prüfen.
22. PageSpeed/CrUX-Messung für mobile Core Web Vitals.
23. Redirect-Kette http → https → non-www prüfen, Ziel: maximal ein Hop.
24. Backlink-Check: Führen alte, extern verlinkte URLs (z. B. `*.html` aus der Yuuble-Zeit) jetzt auf 404? Falls ja, 301 setzen.

---

## Anhang A – Seitenmatrix (Kurzfassung)

| Bereich | Seiten | Title/Desc OK | JSON-LD | Hinweise |
|---|---|---|---|---|
| Startseite | 1 | ✅ | ✅ umfangreich | AggregateRating entfernen |
| Ultraschallgeräte (Übersicht + 3 Kategorien) | 4 | ✅ | ✅ | 4 Bilder ohne Alt auf `/ultraschallgeraete` |
| Produkte Standgeräte | 12 | ✅ | ✅ | price 0 |
| Produkte Mobil | 16 | ⚠️ 13/16 | ⚠️ 13/16 | C25, X1 Go, MU7 unvollständig |
| Produkte Handheld | 2 | ✅ | ✅ | price 0 |
| Gebrauchtgeräte | 1 (+1 noindex) | ✅ | ✅ | Platzhalterbild |
| Sono Finder | 1 | ❌ | ❌ | Stub, Thin Content |
| Fachbereiche `/fuer` | 14 | ⚠️ 13/14 | ⚠️ 10/14 | Wirtschaftlichkeit unvollständig, Blog-Links kaputt |
| Hersteller | 4 | ✅ | ✅ | |
| Leistungen | 9 | ✅ | ✅ | |
| Standorte | 6 | ⚠️ 5/6 | ⚠️ 5/6 | Digitale Sonothek unvollständig, NAP |
| Über uns | 3 | ✅ | ✅ | |
| Karriere / Kontakt / Veranstaltungen | 3 | ✅ | ✅ | Telefonnummern auf Kontakt |
| Blog | 1 | ✅ | ❌ | keine Inhalte |
| Rechtliches / Danke | 4 | – noindex | – | aus Sitemap entfernen, H1 ergänzen |

## Anhang B – Geprüfte Dateien
`routes/web.php`, `bootstrap/app.php`, `resources/views/**/*.blade.php` (alle 81 Seiten-Views + Layout-Komponenten), `resources/sitemap/*.xml`, `public/robots.txt`, `nginx.template.conf`, `nixpacks.toml`, `resources/css/*.css`, `resources/js/consent.js`, `public/assets/img/**`, `ROADMAP.md`, `tests/Feature/*`.
