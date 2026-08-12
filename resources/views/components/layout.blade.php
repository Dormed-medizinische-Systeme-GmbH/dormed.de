<!DOCTYPE html>
<html lang="de-DE" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="/assets/img/9166be6b-46c0-4068-8b57-2b414dd62000.png">
    <link rel="apple-touch-icon" href="/assets/img/9166be6b-46c0-4068-8b57-2b414dd62000.png">

    <script src="https://rybbit.everding.it/api/script.js" data-site-id="d8a38f08fa4f" defer></script>

    @vite(['resources/css/style.css', 'resources/css/widgets.css', 'resources/js/consent.js'])

    <style id="yuuble-theme-style">
    :root{

}

    </style>

    {{ $head }}
  </head>
  <body>

<x-layout.header />

{{ $slot }}

<x-layout.footer />

<x-layout.consent-banner />

  </body>
</html>
