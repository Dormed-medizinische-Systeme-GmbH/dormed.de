<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CAS-Synchronisation der Veranstaltungen
    |--------------------------------------------------------------------------
    |
    | Webhook POST /webhooks/cas/veranstaltungen -> Job SyncCalendarEvent ->
    | CalendarEventSynchronizer holt den Termin per GUID aus CAS und gleicht
    | ihn mit der Tabelle calendar_events ab.
    |
    */

    'data_object_type' => 'APPOINTMENT',

    /*
    | Nur Termine mit diesem Typ (CAS-Feld GWSTYPE) kommen auf die Website.
    | Der CAS-Webhook filtert bereits darauf; der Abgleich prüft es
    | zusätzlich, damit ein Termin, dessen Typ in CAS geändert wird, beim
    | nächsten Sync verschwindet.
    */
    'gwstype' => 'Veranstaltung',

    /*
    | CAS-Kategorie (Feld CATEGORY = Veranstaltungsart) -> Typ auf der Seite
    | (Badge, Farbe, Filter). Veranstaltungen ohne oder mit einer hier nicht
    | eingetragenen Kategorie werden lokal entfernt.
    */
    'categories' => [
        'Messe' => 'messe',
        'Fachtagung' => 'fachtagung',
    ],

    /*
    | Optionale CAS-Felder. null = nicht übernommen. Leere Felder liefert CAS
    | gar nicht mit; dann ist die Beschreibung leer, die Anmeldung verlinkt
    | auf /kontakt und der Status ist "Geplant".
    */
    'fields' => [
        'description' => null,
        'registration_url' => 'VERANSTALTUNGS_LINK',
        'status' => 'GWSSTATUS',
    ],

    /*
    | Werte der CAS-Auswahlliste -> interner Status (siehe
    | CalendarEvent::STATUSES). Leer oder unbekannt gilt als "Geplant".
    | Steht ein neues Datum fest, werden Start/Ende angepasst und der Status
    | zurück auf "Geplant" gesetzt - einen eigenen Status dafür gibt es nicht.
    */
    'statuses' => [
        'Geplant' => 'scheduled',
        'Verschoben' => 'postponed',
        'Abgesagt' => 'cancelled',
        'Online' => 'moved_online',
    ],

];
