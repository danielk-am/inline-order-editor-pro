# Quality workflow

`.github/workflows/quality.yml` runs on pull requests, pushes and manual dispatch. It has no deployment step and grants only repository read permission. Each job has its own disposable MySQL database and loopback WordPress installation; it never uses a merchant account or production credentials.

The compatibility matrix covers PHP 7.4 / WordPress 6.8 / WooCommerce 10.0 / posts storage; PHP 8.3 / latest WordPress and WooCommerce / HPOS; PHP 8.4 / latest WordPress and WooCommerce / posts. This is a sample of supported combinations, not every version permutation. Latest versions are recorded in artifacts. Pro requires checkout access to the free repository; if it is private, configure the read-only `CORE_REPO_TOKEN` Actions secret. Fork pull requests without access fail at that dependency checkout instead of reporting a false pass.

Gates:

1. PHP syntax, fresh distribution build, package metadata checks and exact ZIP boundary inspection.
2. Install and activate that ZIP, with WooCommerce and the free plugin first for Pro.
3. Official current WordPress Plugin Check against the installed slug. Full output, stderr and checker version are retained. Every error and warning fails, including dependency-directory warnings. Checker invocation failures fail independently of findings.
4. PHPUnit 9.6 integration tests against real WordPress/WooCommerce APIs, with JUnit and Xdebug Clover reports. The runner includes the installed ZIP’s source in coverage filters; the test process has a 512 MB memory limit. The test bootstrap blocks mail/external HTTP before fixture mutations. No artificial minimum coverage percentage is presented as a safety guarantee.
5. Pro's standalone stub regression scripts run in separate PHP processes on PHP 8.x, including all three module load-order cases. Their assertions use PHP 8 string helpers, so that layer is intentionally not run on PHP 7.4; PHPUnit and PHP syntax still run there.

Artifacts retain the exact ZIP, package/activation/platform/checker facts, PHPUnit report, coverage and runtime debug log. A Chromium smoke job on PHP 8.3 opens a real disposable order, checks the order form (and Pro sections/export widgets), captures screenshots at 1440, 768 and 390 pixels, and rejects page JavaScript errors. It does not replace independent council UAT or prove every control works. Service-backed transactions remain separate evidence. No plugin upload, WordPress.org submission, GitHub release or production deployment is automated.

Local commands:

```sh
bash bin/ci-quality.sh
IOEFW_TEST_WP_ROOT=/path/to/disposable/wp IOEFW_TEST_ALLOW_MUTATIONS=1 bash bin/run-phpunit.sh
```

Only run integration commands on a disposable local database. `bin/ci-plugin-check.php` evaluates the preserved official CLI report without dropping warnings. The workflow definition is prepared locally until committed/pushed and executed by GitHub Actions; local runs are not hosted Actions results.
