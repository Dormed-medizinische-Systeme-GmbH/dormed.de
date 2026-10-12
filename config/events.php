<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Veranstaltungen mit Lead-Formular
    |--------------------------------------------------------------------------
    |
    | Jede Veranstaltung, für die ein QR-Code gedruckt wird, braucht hier einen
    | Eintrag. Der Schlüssel ist der Slug in der URL
    | (/veranstaltungen/{slug}/kontakt) und darf sich nach dem Druck des
    | QR-Codes nicht mehr ändern. Unbekannte Slugs liefern 404.
    |
    | Nach "accepts_leads_until" (einschließlich) zeigt die Seite statt des
    | Formulars einen Hinweis mit Link auf /kontakt.
    |
    */

    'medica-2026' => [
        'name' => 'MEDICA 2026',
        'location' => 'Messe Düsseldorf',
        'accepts_leads_until' => '2026-12-31',
    ],

];
