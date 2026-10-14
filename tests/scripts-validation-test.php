<?php

declare(strict_types=1);
require dirname(__DIR__) . '/app/scripts.php';
$valid = ['hack.js', 'dnet-heartbleed.js', 'a_b.c.js'];
$invalid = ['../hack.js', 'bad name.js', 'hack.php', 'x.js\nContent-Type: text/html', '.hidden.js'];
foreach ($valid as $name) {
    if (!validScriptFilename($name)) throw new RuntimeException('Expected valid: ' . $name);
}
foreach ($invalid as $name) {
    if (validScriptFilename($name)) throw new RuntimeException('Expected invalid: ' . $name);
}
$catalog = json_decode((string) file_get_contents(dirname(__DIR__) . '/resources/builtin-scripts/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
if (count($catalog) !== 16) throw new RuntimeException('Expected exactly sixteen helpers');
foreach ($catalog as $entry) {
    $file = dirname(__DIR__) . '/resources/builtin-scripts/' . $entry['location'];
    if (!is_file($file) || trim((string) file_get_contents($file)) === '') throw new RuntimeException('Missing script ' . $file);
    if (!validScriptFilename($entry['filename'])) throw new RuntimeException('Invalid name');
}
echo "Script library validation tests passed.\n";
