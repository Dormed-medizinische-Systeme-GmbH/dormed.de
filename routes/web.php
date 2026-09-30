<?php

use App\Http\Controllers\ContactFormController;
use Illuminate\Support\Facades\Route;

// Core Rule 2: sitemap.xml/sitemap-system-pages.xml werden über eine Route ausgeliefert,
// nicht als statische Datei in public/ - Inhalt aktuell 1:1 die alte Datei, damit sie
// später ohne URL-Änderung dynamisch generiert werden können.
Route::get('/sitemap.xml', function () {
    return response(file_get_contents(resource_path('sitemap/sitemap.xml')))
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/sitemap-system-pages.xml', function () {
    return response(file_get_contents(resource_path('sitemap/sitemap-system-pages.xml')))
        ->header('Content-Type', 'application/xml');
})->name('sitemap.system-pages');

// KI-Crawler-Alternative je Produktseite: /ultraschallgeraete/{...}.md liefert die
// Markdown-Datei aus, die direkt neben der zugehoerigen Blade-View liegt
// (resources/views/ultraschallgeraete/{...}.md) - existiert nur fuer Produkte, die
// bereits ein Markdown-Kurzprofil haben, sonst 404.
Route::get('/ultraschallgeraete/{path}.md', function (string $path) {
    $file = resource_path("views/ultraschallgeraete/{$path}.md");

    abort_unless(is_file($file), 404);

    return response(file_get_contents($file))
        ->header('Content-Type', 'text/markdown; charset=UTF-8');
})->where('path', '[a-z0-9\-\/]+')->name('ultraschallgeraete.markdown');

Route::view('/blog', 'blog.index')->name('blog.index');
Route::view('/danke', 'danke')->name('danke');
Route::view('/fuer/allgemeinmedizin', 'fuer.allgemeinmedizin.index')->name('fuer.allgemeinmedizin.index');
Route::view('/fuer/allgemeinmedizin/leber-elastographie', 'fuer.allgemeinmedizin.leber-elastographie')->name('fuer.allgemeinmedizin.leber-elastographie');
Route::view('/fuer/allgemeinmedizin/schilddruesen-sonographie', 'fuer.allgemeinmedizin.schilddruesen-sonographie')->name('fuer.allgemeinmedizin.schilddruesen-sonographie');
Route::view('/fuer/allgemeinmedizin/sonographie', 'fuer.allgemeinmedizin.sonographie')->name('fuer.allgemeinmedizin.sonographie');
Route::view('/fuer/gynaekologie', 'fuer.gynaekologie.index')->name('fuer.gynaekologie.index');
Route::view('/fuer/gynaekologie/vaginaler-ultraschall', 'fuer.gynaekologie.vaginaler-ultraschall')->name('fuer.gynaekologie.vaginaler-ultraschall');
Route::view('/fuer', 'fuer.index')->name('fuer.index');
Route::view('/fuer/kardiologie/cw-doppler', 'fuer.kardiologie.cw-doppler')->name('fuer.kardiologie.cw-doppler');
Route::view('/fuer/kardiologie/echokardiographie', 'fuer.kardiologie.echokardiographie')->name('fuer.kardiologie.echokardiographie');
Route::view('/fuer/kardiologie/farbduplexsonographie', 'fuer.kardiologie.farbduplexsonographie')->name('fuer.kardiologie.farbduplexsonographie');
Route::view('/fuer/kardiologie', 'fuer.kardiologie.index')->name('fuer.kardiologie.index');
Route::view('/fuer/kardiologie/pw-doppler', 'fuer.kardiologie.pw-doppler')->name('fuer.kardiologie.pw-doppler');
Route::view('/fuer/kardiologie/wirtschaftlichkeit', 'fuer.kardiologie.wirtschaftlichkeit')->name('fuer.kardiologie.wirtschaftlichkeit');
Route::view('/fuer/orthopaedie', 'fuer.orthopaedie')->name('fuer.orthopaedie');
Route::view('/hersteller/chison', 'hersteller.chison')->name('hersteller.chison');
Route::view('/hersteller/esaote', 'hersteller.esaote')->name('hersteller.esaote');
Route::view('/hersteller', 'hersteller.index')->name('hersteller.index');
Route::view('/hersteller/mindray', 'hersteller.mindray')->name('hersteller.mindray');
Route::view('/', 'index')->name('index');
Route::view('/karriere', 'karriere')->name('karriere');
Route::view('/kontakt', 'kontakt')->name('kontakt');
Route::post('/kontakt', [ContactFormController::class, 'store'])->name('kontakt.store');
Route::view('/leistungen/beratung', 'leistungen.beratung')->name('leistungen.beratung');
Route::view('/leistungen/finanzierung', 'leistungen.finanzierung')->name('leistungen.finanzierung');
Route::view('/leistungen/garantie-versicherung', 'leistungen.garantie-versicherung')->name('leistungen.garantie-versicherung');
Route::view('/leistungen', 'leistungen.index')->name('leistungen.index');
Route::view('/leistungen/inzahlungnahme', 'leistungen.inzahlungnahme')->name('leistungen.inzahlungnahme');
Route::view('/leistungen/lieferung', 'leistungen.lieferung')->name('leistungen.lieferung');
Route::view('/leistungen/netzwerkanbindung', 'leistungen.netzwerkanbindung')->name('leistungen.netzwerkanbindung');
Route::view('/leistungen/schulung-einweisung', 'leistungen.schulung-einweisung')->name('leistungen.schulung-einweisung');
Route::view('/leistungen/wartung-reparatur', 'leistungen.wartung-reparatur')->name('leistungen.wartung-reparatur');
Route::view('/standorte/digitale-sonothek', 'standorte.digitale-sonothek')->name('standorte.digitale-sonothek');
Route::view('/standorte/dortmund', 'standorte.dortmund')->name('standorte.dortmund');
Route::view('/standorte/duesseldorf', 'standorte.duesseldorf')->name('standorte.duesseldorf');
Route::view('/standorte/hamburg', 'standorte.hamburg')->name('standorte.hamburg');
Route::view('/standorte', 'standorte.index')->name('standorte.index');
Route::view('/standorte/kiel', 'standorte.kiel')->name('standorte.kiel');
Route::view('/ueber/dormed', 'ueber.dormed')->name('ueber.dormed');
Route::view('/ueber', 'ueber.index')->name('ueber.index');
Route::view('/ueber/sonoring', 'ueber.sonoring')->name('ueber.sonoring');
Route::view('/ultraschallgeraete/gebraucht', 'ultraschallgeraete.gebraucht')->name('ultraschallgeraete.gebraucht');
Route::view('/ultraschallgeraete/handheld', 'ultraschallgeraete.handheld.index')->name('ultraschallgeraete.handheld.index');
Route::view('/ultraschallgeraete/handheld/mindray-te-air-e5m', 'ultraschallgeraete.handheld.mindray-te-air-e5m')->name('ultraschallgeraete.handheld.mindray-te-air-e5m');
Route::view('/ultraschallgeraete/handheld/mindray-te-air-i3m', 'ultraschallgeraete.handheld.mindray-te-air-i3m')->name('ultraschallgeraete.handheld.mindray-te-air-i3m');
Route::view('/ultraschallgeraete', 'ultraschallgeraete.index')->name('ultraschallgeraete.index');
Route::view('/ultraschallgeraete/mobile-geraete/chison-sonoair-70', 'ultraschallgeraete.mobile-geraete.chison-sonoair-70')->name('ultraschallgeraete.mobile-geraete.chison-sonoair-70');
Route::view('/ultraschallgeraete/mobile-geraete/esaote-mylab-c25', 'ultraschallgeraete.mobile-geraete.esaote-mylab-c25')->name('ultraschallgeraete.mobile-geraete.esaote-mylab-c25');
Route::view('/ultraschallgeraete/mobile-geraete/esaote-mylab-x1-go', 'ultraschallgeraete.mobile-geraete.esaote-mylab-x1-go')->name('ultraschallgeraete.mobile-geraete.esaote-mylab-x1-go');
Route::view('/ultraschallgeraete/mobile-geraete', 'ultraschallgeraete.mobile-geraete.index')->name('ultraschallgeraete.mobile-geraete.index');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-dp-10', 'ultraschallgeraete.mobile-geraete.mindray-dp-10')->name('ultraschallgeraete.mobile-geraete.mindray-dp-10');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-dp-30', 'ultraschallgeraete.mobile-geraete.mindray-dp-30')->name('ultraschallgeraete.mobile-geraete.mindray-dp-30');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-dp-50', 'ultraschallgeraete.mobile-geraete.mindray-dp-50')->name('ultraschallgeraete.mobile-geraete.mindray-dp-50');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-dp-60', 'ultraschallgeraete.mobile-geraete.mindray-dp-60')->name('ultraschallgeraete.mobile-geraete.mindray-dp-60');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-mu7', 'ultraschallgeraete.mobile-geraete.mindray-mu7')->name('ultraschallgeraete.mobile-geraete.mindray-mu7');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-mx3', 'ultraschallgeraete.mobile-geraete.mindray-mx3')->name('ultraschallgeraete.mobile-geraete.mindray-mx3');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-mx5', 'ultraschallgeraete.mobile-geraete.mindray-mx5')->name('ultraschallgeraete.mobile-geraete.mindray-mx5');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-mx7', 'ultraschallgeraete.mobile-geraete.mindray-mx7')->name('ultraschallgeraete.mobile-geraete.mindray-mx7');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-te-5', 'ultraschallgeraete.mobile-geraete.mindray-te-5')->name('ultraschallgeraete.mobile-geraete.mindray-te-5');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-te-7-ace', 'ultraschallgeraete.mobile-geraete.mindray-te-7-ace')->name('ultraschallgeraete.mobile-geraete.mindray-te-7-ace');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-te-9', 'ultraschallgeraete.mobile-geraete.mindray-te-9')->name('ultraschallgeraete.mobile-geraete.mindray-te-9');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-z50', 'ultraschallgeraete.mobile-geraete.mindray-z50')->name('ultraschallgeraete.mobile-geraete.mindray-z50');
Route::view('/ultraschallgeraete/mobile-geraete/mindray-z60', 'ultraschallgeraete.mobile-geraete.mindray-z60')->name('ultraschallgeraete.mobile-geraete.mindray-z60');
Route::view('/ultraschallgeraete/sono-finder', 'ultraschallgeraete.sono-finder')->name('ultraschallgeraete.sono-finder');
Route::view('/ultraschallgeraete/standgeraete/esaote-mylab-a50', 'ultraschallgeraete.standgeraete.esaote-mylab-a50')->name('ultraschallgeraete.standgeraete.esaote-mylab-a50');
Route::view('/ultraschallgeraete/standgeraete/esaote-mylab-a70', 'ultraschallgeraete.standgeraete.esaote-mylab-a70')->name('ultraschallgeraete.standgeraete.esaote-mylab-a70');
Route::view('/ultraschallgeraete/standgeraete', 'ultraschallgeraete.standgeraete.index')->name('ultraschallgeraete.standgeraete.index');
Route::view('/ultraschallgeraete/standgeraete/mindray-consona-n5', 'ultraschallgeraete.standgeraete.mindray-consona-n5')->name('ultraschallgeraete.standgeraete.mindray-consona-n5');
Route::view('/ultraschallgeraete/standgeraete/mindray-consona-n6', 'ultraschallgeraete.standgeraete.mindray-consona-n6')->name('ultraschallgeraete.standgeraete.mindray-consona-n6');
Route::view('/ultraschallgeraete/standgeraete/mindray-consona-n8', 'ultraschallgeraete.standgeraete.mindray-consona-n8')->name('ultraschallgeraete.standgeraete.mindray-consona-n8');
Route::view('/ultraschallgeraete/standgeraete/mindray-consona-n9', 'ultraschallgeraete.standgeraete.mindray-consona-n9')->name('ultraschallgeraete.standgeraete.mindray-consona-n9');
Route::view('/ultraschallgeraete/standgeraete/mindray-dc-30', 'ultraschallgeraete.standgeraete.mindray-dc-30')->name('ultraschallgeraete.standgeraete.mindray-dc-30');
Route::view('/ultraschallgeraete/standgeraete/mindray-dc-60', 'ultraschallgeraete.standgeraete.mindray-dc-60')->name('ultraschallgeraete.standgeraete.mindray-dc-60');
Route::view('/ultraschallgeraete/standgeraete/mindray-nuewa-i10', 'ultraschallgeraete.standgeraete.mindray-nuewa-i10')->name('ultraschallgeraete.standgeraete.mindray-nuewa-i10');
Route::view('/ultraschallgeraete/standgeraete/mindray-nuewa-i9', 'ultraschallgeraete.standgeraete.mindray-nuewa-i9')->name('ultraschallgeraete.standgeraete.mindray-nuewa-i9');
Route::view('/ultraschallgeraete/standgeraete/mindray-resona-i8', 'ultraschallgeraete.standgeraete.mindray-resona-i8')->name('ultraschallgeraete.standgeraete.mindray-resona-i8');
Route::view('/ultraschallgeraete/standgeraete/mindray-resona-i9', 'ultraschallgeraete.standgeraete.mindray-resona-i9')->name('ultraschallgeraete.standgeraete.mindray-resona-i9');
Route::view('/veranstaltungen', 'veranstaltungen')->name('veranstaltungen');
