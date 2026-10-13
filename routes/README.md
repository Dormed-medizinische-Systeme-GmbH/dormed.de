# routes

`web.php` definiert alle Routen, `console.php` den Scheduler ([app](../app/README.md#queue-scheduler-retries)).

## Regeln

- **URLs ändern sich nie.** Neue Seiten sind erlaubt, bestehende Pfade bleiben, auch bei
  Umbauten.
- **Eine `Route::view()` je statische Seite, jede benannt**, Pfad = Ordnerstruktur unter
  `resources/views`. Nur Seiten mit Logik bekommen einen Controller (Formulare, Sync-
  gespeiste Arbeitskopien, Webhooks). `StaticPagesTest` ruft jede GET-Route ohne Parameter
  auf; eine neue Seite wird dadurch automatisch auf Erreichbarkeit geprüft.
- **Sitemaps** laufen als Routen (`/sitemap.xml`, `/sitemap-system-pages.xml`), Inhalt in
  `resources/sitemap/` ([resources](../resources/README.md)).
- **`/ultraschallgeraete/{path}.md`** liefert das Markdown-Profil neben der Produkt-View
  aus, mit 404 ohne Datei ([views](../resources/views/README.md#produktseiten)).
- **Event-Lead-Routen** (`/veranstaltungen/{event}/kontakt…`) sind nur per QR-Code
  erreichbar: nicht verlinkt, nicht in der Sitemap, `noindex`.
- **Webhooks** (`/webhooks/cas/…`) liegen hinter Bearer-Token und `throttle:60,1`
  ([Services/Cas](../app/Services/Cas/README.md#webhooks-cas--website)).
