# app/Services/UsedDevices

Spiegelt Gebrauchtgeräte aus CAS (Datentyp `GEBRAUCHTGERAETE`) in die Tabelle
`used_devices` und lädt deren Bilder auf den öffentlichen Datenträger. Quelle der Wahrheit
bleibt CAS. Gerendert wird die Arbeitskopie `/ultraschallgeraete/gebraucht2`
(`UsedDeviceController`); `/ultraschallgeraete/gebraucht` ist weiterhin statisches Markup.

## Ablauf

CAS-Webhook `POST /webhooks/cas/gebrauchtgeraete` → Job `SyncUsedDevice` →
`UsedDeviceSynchronizer::sync($guid)`:

- **speichern**, wenn das Gerät gefunden wird;
- **lokal entfernen** (Zeile und Bildordner), wenn es in CAS gelöscht wurde (`404`) oder
  `ONLINE` ausdrücklich `false` ist;
- **nichts ändern und werfen** bei jedem anderen CAS-Fehler, damit die Queue wiederholt.

Ohne Webhook: `php artisan used-devices:sync {gguid…}`; ohne Argumente werden alle
**lokal bekannten** Geräte neu abgeglichen.

## Regeln

- **Bilder zuerst, Zeile danach:** Scheitert ein Download, ist die Zeile noch unberührt
  und der Retry startet vom gleichen Stand. Bilder liegen unter
  `storage/app/public/gebraucht/{GUID}/{Dokument-GUID}.{ext}`, erreichbar über
  `/storage/…` (setzt `php artisan storage:link` voraus). Erlaubt sind `jpg`, `jpeg`, `png`.
- **Das Dossier wird bei jedem Sync geprüft**, weil ein neues Dokument den ETag des
  Datensatzes nicht zwingend ändert: fehlende Bilder werden geladen, nicht mehr
  vorhandene gelöscht, die Reihenfolge folgt `SORTDATE`.
- **Freitextfelder in CAS:** Das Baujahr kann `2021`, `2021-01-13` oder `07/2009` heißen,
  der Synchronizer nimmt die vierstellige Jahreszahl heraus. Von den Sondenplätzen 1–4
  werden die Platzhaltertexte `Sonde N …` übersprungen.
- **Filter der Seite** leitet das Modell ab: Hersteller = erstes Wort von
  `manufacturer` („Mindray Co. Ltd." → „Mindray"), System aus `description`
  (enthält „farb" → Farbdoppler, „schwarz" → Schwarz-/Weiß, sonst Sonstige), Baujahrband
  aus `year`.
- Die Webhook-Mechanik (Token, Payload, `202`) steht in
  [Services/Cas](../Cas/README.md#webhooks-cas--website).
