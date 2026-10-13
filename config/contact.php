<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Erlaubte Link-Domains im Kontaktformular
    |--------------------------------------------------------------------------
    |
    | Links in Freitextfeldern des Kontaktformulars werden abgelehnt
    | (Spamschutz), außer sie zeigen auf eine dieser Domains. Subdomains sind
    | automatisch mit erlaubt (www.dormed.de, shop.dormed.de, ...).
    |
    */

    'allowed_link_domains' => [
        'dormed.de',
    ],

    /*
    |--------------------------------------------------------------------------
    | Erlaubte Schriften in der Nachricht
    |--------------------------------------------------------------------------
    |
    | Unicode-Schriften, aus denen die Nachricht bestehen darf. "Common" deckt
    | Ziffern, Satzzeichen, Symbole (€, %, µ) und Emojis ab, "Inherited"
    | kombinierte Akzente. Alles andere (Kyrillisch, Georgisch, Arabisch, CJK,
    | ...) wird abgelehnt - im Frontend schon beim Tippen verhindert.
    |
    */

    'allowed_scripts' => [
        'Latin',
        'Common',
        'Inherited',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocklist (stilles Verwerfen)
    |--------------------------------------------------------------------------
    |
    | Gilt global fuer alle Formulare (Middleware BlockListedSubmissions in der
    | web-Gruppe), nicht nur fuer das Kontaktformular. Trifft ein Feld einen
    | Eintrag, sieht die Person ganz normal die Danke-Seite - Mails, CAS und
    | alle anderen Jobs werden aber nicht ausgeloest. Verworfenes steht in
    | spam.log.
    |
    | Verglichen wird immer der GANZE Wert, nicht Teile davon:
    |  - name, praxis: ohne Beachtung von Gross-/Kleinschreibung, Leerraum
    |    zusammengezogen. Das Feld "praxis_name" (Lead-Formular, "Praxis / Name")
    |    wird gegen beide Listen geprueft.
    |  - email: kanonisiert (klein, ohne unsichtbare Zeichen, ohne "+tag",
    |    bei Gmail zusaetzlich ohne Punkte). Eintraege hier in diesem Format
    |    eintragen, also z. B. "name@hotmail.de" oder "vorname@gmail.com".
    |  - telefon: nur Ziffern, Vorwahl +49 / 0049 wie fuehrende 0.
    |
    */

    'blocklist' => [
        'name' => [
            'Robertloult',
        ],
        'email' => [
            'filip.b@hotmail.de',
        ],
        'telefon' => [],
        'praxis' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Weiterleitung nach stillem Verwerfen
    |--------------------------------------------------------------------------
    |
    | POST-Route (Name) => Danke-Seite (Route). Neue Formulare hier eintragen,
    | sonst wird bei einem Treffer ohne Meldung zur Formularseite zurueck
    | geleitet. Route-Parameter (z. B. {event}) werden uebernommen.
    |
    */

    'blocklist_redirects' => [
        'kontakt.store' => 'danke',
        'veranstaltungen.lead.store' => 'veranstaltungen.lead.danke',
    ],

];
