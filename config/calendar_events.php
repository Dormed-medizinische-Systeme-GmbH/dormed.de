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
