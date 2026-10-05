<?php

test('the legal pages are reachable and show their content', function (string $uri, string $heading) {
    $this->withoutVite();

    $this->get($uri)->assertOk()->assertSeeText($heading);
})->with([
    'impressum' => ['/impressum', 'Impressum'],
    'datenschutz' => ['/datenschutz', 'Datenschutz'],
    'agb' => ['/agb', 'Allgemeine Geschäftsbedingungen'],
]);

test('every legal link in the footer resolves to an existing page', function () {
    $this->withoutVite();

    $footer = file_get_contents(resource_path('views/components/layout/footer.blade.php'));

    preg_match_all('#href="(/(?:impressum|datenschutz|agb))"#', $footer, $matches);

    expect($matches[1])->toEqualCanonicalizing(['/impressum', '/datenschutz', '/agb']);

    foreach ($matches[1] as $uri) {
        $this->get($uri)->assertOk();
    }
});
