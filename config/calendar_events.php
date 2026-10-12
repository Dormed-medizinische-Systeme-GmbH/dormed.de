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
    | CAS-Kategorie (Feld CATEGORY = Veranstaltungsart) -> Typ auf der Seite
    | (Badge, Farbe, Filter). Nur Termine in einer hier eingetragenen
    | Kategorie kommen auf die Website, alle anderen werden lokal entfernt -
    | solange es keine Prüfung gegen die CAS-View gibt, ist das der Filter.
    */
    'categories' => [
        'Messe/Kongreß/Veranstaltung' => 'messe',
    ],

    /*
    | Optionale CAS-Felder, die es noch nicht gibt. null = nicht übernommen
    | (Beschreibung leer, Anmeldung verlinkt auf /kontakt, Status "scheduled").
    */
    'fields' => [
        'description' => null,
        'registration_url' => null,
        'status' => null,
    ],

    /*
    | Werte des CAS-Statusfelds -> interner Status (siehe
    | CalendarEvent::STATUSES). Unbekannte Werte gelten als "scheduled".
    */
    'statuses' => [
        // 'Abgesagt' => 'cancelled',
        // 'Verschoben' => 'postponed',
        // 'Neuer Termin' => 'rescheduled',
    ],

];
