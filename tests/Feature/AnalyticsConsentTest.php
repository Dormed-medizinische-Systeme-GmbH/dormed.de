<?php

test('the analytics script is only activated by consent to the analytics category', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<script type="text/plain" data-category="analytics" src="https://rybbit.everding.it/api/script.js"')
        ->not->toMatch('~<script(?![^>]*type="text/plain")[^>]*rybbit\.everding\.it~');
});
