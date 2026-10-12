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

];
