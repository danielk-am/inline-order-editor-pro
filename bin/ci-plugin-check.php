<?php
// CLI Plugin Check prints one JSON array per file, prefixed with FILE: headings.
$lines = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false || !$lines) { throw new RuntimeException('Missing Plugin Check report'); }
$errors = 0; $warnings = 0; $reports = 0;
if ($lines === array('Success: Checks complete. No errors found.')) { echo "PASS Plugin Check: no findings\n"; exit(0); }
foreach ($lines as $line) {
    if (strpos($line, 'FILE: ') === 0) { continue; }
    $items = json_decode($line);
    if (!is_array($items)) { throw new RuntimeException('Unexpected Plugin Check output: ' . $line); }
    ++$reports;
    foreach ($items as $item) {
        if (!is_object($item)) { throw new RuntimeException('Malformed finding'); }
        $item = (array) $item;
        if (!isset($item['type'], $item['code'])) { throw new RuntimeException('Malformed finding'); }
        $type = strtoupper($item['type']);
        if ($type === 'ERROR') { ++$errors; }
        elseif ($type === 'WARNING') {
            ++$warnings;
            ++$errors;
        } else { throw new RuntimeException('Unexpected finding severity'); }
    }
}
if (!$reports) { throw new RuntimeException('No JSON reports'); }
echo "Plugin Check: $errors blocking findings, $warnings warnings (full JSON retained).\n";
exit($errors ? 1 : 0);
