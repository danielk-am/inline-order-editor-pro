<?php
// CI-only package boundary gate. Never shipped.
$zip = new ZipArchive();
if (true !== $zip->open($argv[1])) { throw new RuntimeException('Cannot open ZIP'); }
$slug = $argv[2];
for ($i = 0; $i < $zip->numFiles; ++$i) {
    $name = $zip->getNameIndex($i);
    if (strpos($name, $slug . '/') !== 0 || preg_match('~^' . preg_quote($slug, '~') . '/(?:\.git|\.github|tests|bin|docs|pdf-dependencies|node_modules|composer\.(?:json|lock)|phpunit[^/]*)(?:/|$)|(?:^|/)(?:\.env|\._[^/]+)(?:/|$)~', $name)) {
        throw new RuntimeException('Unexpected package member: ' . $name);
    }
}
if (false === $zip->locateName($slug . '/' . $slug . '.php')) { throw new RuntimeException('Missing entry point'); }
echo "PASS exact ZIP boundaries and main entry point\n";
