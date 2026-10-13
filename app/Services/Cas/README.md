# app/Services/Cas

`CasClient`: schlanker Wrapper um die REST-API von CAS genesisWorld (v7.0) auf Basis der
`Http`-Facade. Keine zusätzliche Dependency; bei mehreren CRM-Integrationen wäre Saloon
eine Option, nur nach Rücksprache.

## Regeln

- **Zugang:** Basic Auth (`username:password`, **ohne** Datenbank-Präfix) plus Header
  `X-CAS-PRODUCT-KEY`. Env-Variablen:
  `CAS_GENESIS_WORLD_HOST`, `_USERNAME`, `_PASSWORD`, `_PRODUCT_KEY`; für eingehende
  Webhooks zusätzlich `_WEBHOOK_BEARER`. Fehlt das Schema im Host, setzt der Client
  `https://` vor.
- **Die Swagger-Doku ist zu etwa 95 % korrekt**; einzelne Payload-/Response-Schemas sind
  falsch verlinkt (`CreateDataObject`/`GetDataObject` zeigen auf
  `CheckForContactDuplicatesRequestData`) oder als „undocumented" markiert. Deshalb loggt
  der Client jeden Schreibaufruf mit Roh-Request und -Response in `api.log`, und das
  Rohprotokoll ist die verlässliche Referenz.
- **Create:** Der Body trägt die Werte unter `fields` (`fields` ist neben den hier
  ungenutzten Permission-Keys der einzige erlaubte Top-Level-Key); flach im Body antwortet
  CAS mit `400 ILLEGAL_ARGUMENT_VALUE`. Eine erfolgreiche Anlage antwortet
  `201` mit leerem Body, die GUID steht als letztes Pfadsegment im `Location`-Header. Die
  GUID dient nur dem Logging; ein nicht auswertbarer Header zählt trotzdem als Erfolg.
- **Lesen (`getDataObject`):** Nur `404` bedeutet „in CAS gelöscht" und liefert `null`.
  Jeder andere Fehler wirft `CasRequestFailedException`, damit die Queue wiederholt und
  ein Ausfall nie als Löschung gilt. Lesezugriffe loggt der Client als Zusammenfassung in
  `webhook.api.log`.
- **GUIDs** normalisiert `CasClient::normalizeGuid` auf 32 Zeichen Großbuchstaben-Hex ohne
  Klammern und Bindestriche; CAS sendet sie in beliebiger Schreibweise.
- **CAS startet nachts etwa eine Stunde neu.** Jobs, die den Client nutzen, wiederholen
  deshalb bis 3 Stunden (siehe [app](../../README.md#queue-scheduler-retries)).

## Tabelle `Inquiries` (Kontaktformular)

Exakte Namen und Längen, wie im CRM angelegt. Die Formularvalidierung
(`ContactFormRequest`) hält diese Längen ein, damit CAS nichts abschneidet.

| CAS-Feld | Typ | Länge | Formularfeld | Pflicht |
|---|---|---|---|---|
| `Name` | varchar | 128 | `name` | ja |
| `MAIL` | varchar | 64 | `email` | ja |
| `PHONE` | varchar | 32 | `telefon` | nein |
| `ZIP` | varchar | 8 | `plz` | ja |
| `MESSAGE` | varchar(max) | max | `nachricht` | nein |
| `COMPANY` | varchar | 64 | `praxis` | nein |
| `SPECIALTY` | varchar | 64 | `fachgebiet` | nein |
| `CALLBACK_REQUEST` | bit | fix | `rueckruf` | nein |
| `CALLBACK_DATE` | date | fix | `rueckruf_datum` | nein |

`MAIL_STATUS` existiert im CRM, wird aber nicht beschrieben. Die DSGVO-Checkbox
(`datenschutz`) geht nicht an CAS, und das Anlagedatum führt das CRM selbst.

## Webhooks (CAS → Website)

Zwei Routen, beide mit Bearer-Token (`EnsureValidCasWebhookToken`; ein nicht gesetztes
Token lehnt alles mit `503` ab) und `throttle:60,1`:

- `POST /webhooks/cas/gebrauchtgeraete` → [UsedDevices](../UsedDevices/README.md)
- `POST /webhooks/cas/veranstaltungen` → [CalendarEvents](../CalendarEvents/README.md)

Der Body enthält nur `gguid` (und optional `action`). Die Daten holt der Job per API; die
Antwort an CAS ist `202 queued`. `action` wird nur geloggt, abgeglichen wird immer gegen
den aktuellen CAS-Stand.
