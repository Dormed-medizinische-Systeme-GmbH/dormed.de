<?php

use Symfony\Component\Finder\Finder;

/**
 * Alle README.md des Dokumentationsbaums als Pfade relativ zum Projektroot.
 *
 * @return array<int, string>
 */
function docsTreeReadmes(): array
{
    $files = (new Finder)
        ->files()
        ->in(base_path())
        ->ignoreDotFiles(false)
        ->ignoreVCSIgnored(true)
        ->exclude(['.git', '.claude', '.old', 'vendor', 'node_modules'])
        ->name('README.md');

    $paths = [];

    foreach ($files as $file) {
        $paths[] = $file->getRelativePathname();
    }

    sort($paths);

    return $paths;
}

/**
 * Relative Link-Ziele einer README, aufgelöst gegen das Projektroot.
 *
 * @return array<int, string>
 */
function docsTreeLinkTargets(string $readme): array
{
    $dir = dirname($readme);
    $content = (string) preg_replace('/^```.*?^```/ms', '', file_get_contents(base_path($readme)));
    preg_match_all('/\]\(([^)\s#]+)(?:#[^)]*)?\)/', $content, $matches);

    $targets = [];

    foreach ($matches[1] as $link) {
        if (preg_match('#^([a-z][a-z0-9+.-]*:|//)#i', $link)) {
            continue;
        }

        $targets[] = trim(
            (string) preg_replace('#/+#', '/', $dir === '.' ? $link : "{$dir}/{$link}"),
            '/',
        );
    }

    return $targets;
}

/**
 * Normalisiert "a/b/../c" zu "a/c".
 */
function docsTreeNormalize(string $path): string
{
    $parts = [];

    foreach (explode('/', $path) as $segment) {
        if ($segment === '..') {
            array_pop($parts);
        } elseif ($segment !== '.' && $segment !== '') {
            $parts[] = $segment;
        }
    }

    return implode('/', $parts);
}

test('the documentation tree has a root README', function () {
    expect(docsTreeReadmes())->toContain('README.md');
});

test('every relative link in a README points to an existing file or directory', function () {
    $broken = [];

    foreach (docsTreeReadmes() as $readme) {
        foreach (docsTreeLinkTargets($readme) as $target) {
            if (! file_exists(base_path(docsTreeNormalize($target)))) {
                $broken[] = "{$readme} -> {$target}";
            }
        }
    }

    expect($broken)->toBeEmpty('Broken README links: '.implode(', ', $broken));
});

test('every README is linked from the nearest README above it', function () {
    $readmes = docsTreeReadmes();
    $unlinked = [];

    foreach ($readmes as $readme) {
        if ($readme === 'README.md') {
            continue;
        }

        $parentDir = dirname(dirname($readme));

        while ($parentDir !== '.' && ! in_array("{$parentDir}/README.md", $readmes, true)) {
            $parentDir = dirname($parentDir);
        }

        $parent = $parentDir === '.' ? 'README.md' : "{$parentDir}/README.md";
        $linked = array_map('docsTreeNormalize', docsTreeLinkTargets($parent));

        if (! in_array($readme, $linked, true)) {
            $unlinked[] = "{$readme} (expected in {$parent})";
        }
    }

    expect($unlinked)->toBeEmpty('READMEs not linked from their parent: '.implode(', ', $unlinked));
});
