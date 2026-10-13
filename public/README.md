# public

Webroot. Nur statische Dateien, die der Webserver direkt ausliefert.

## Regeln

- **Bilder:** `assets/img/` mit Dateinamen im Format `{uuid}.{ext}` (Hash-Namen aus der
  Migration). Produktbilder, die eindeutig zu einem Produkt gehören, liegen unter
  `assets/img/produkte/{produkt-slug}/`; mehrfach genutzte Stock-Bilder direkt in
  `assets/img/`. **Hash-Namen nicht umbenennen**, Views und Bilder verweisen hart darauf.
- `assets/pdf/` enthält die ausgelieferten PDFs.
- **Keine Sitemap-Dateien hier**; sie kommen über Routen ([resources](../resources/README.md)).
- `public/storage` ist der Symlink auf `storage/app/public` (`php artisan storage:link`,
  gitignored). Darüber werden die Gebrauchtgeräte-Bilder ausgeliefert
  ([UsedDevices](../app/Services/UsedDevices/README.md)); fehlt der Link, bleiben sie leer.
- `public/build` ist das Vite-Ergebnis (gitignored), erzeugt von `npm run build`.
