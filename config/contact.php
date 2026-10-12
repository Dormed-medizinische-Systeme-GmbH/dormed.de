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

];
