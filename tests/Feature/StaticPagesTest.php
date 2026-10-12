<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

// /ultraschallgeraete/gebraucht2 liest aus der used_devices-Tabelle.
uses(RefreshDatabase::class);

test('every static page route responds successfully', function () {
    $uris = collect(Route::getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods()) && ! str_contains($route->uri(), '{'))
        ->map(fn ($route) => '/'.ltrim($route->uri(), '/'))
        ->unique()
        ->values();

    expect($uris)->not->toBeEmpty();

    $failures = [];

    foreach ($uris as $uri) {
        $status = $this->get($uri)->getStatusCode();

        if ($status !== 200) {
            $failures[] = "{$uri} => {$status}";
        }
    }

    $this->assertEmpty($failures, 'Broken routes: '.implode(', ', $failures));
});
