# Local PDF dependency provenance

Known good as of 2026-10-15. Posture: wrap maintained Dompdf and mechanically prefix its runtime dependencies to `AlkamyPdfVendor`; no global aliases and no payment integration. Versions are pinned in `pdf-dependencies/composer.lock`.

- [dompdf/dompdf v3.1.6](https://github.com/dompdf/dompdf), LGPL-2.1; upstream tests/security policy and maintained release series.
- [dompdf/php-font-lib 1.0.2](https://github.com/dompdf/php-font-lib), LGPL-2.1.
- [dompdf/php-svg-lib 1.0.2](https://github.com/dompdf/php-svg-lib), LGPL-3.0. SVG input is blocked by the wrapper; dependency retained because the upstream renderer references it.
- [masterminds/html5 2.11.0](https://github.com/Masterminds/html5-php), MIT.
- [sabberworm/php-css-parser v9.5.0](https://github.com/MyIntervals/PHP-CSS-Parser), MIT.

Each package retains its own licence and notices; bundled fonts retain their licence files. LGPL source is included. Composer audit for the production closure returned no security advisories on this date.

Build-only PHP-Scoper v0.18.20 and its development dependencies never ship. Rebuild with `bin/build-pdf-dependencies.sh /path/to/composer.phar`. `pdf-dependencies/scoper.inc.php` records mechanical repairs for dynamically constructed class names and FontLib's namespace index; these are scope transformations, not functional upstream patches. The custom isolated PSR-4 loader handles Cpdf's upstream classmap entry.

The wrapper disables PHP, JavaScript, remote loading and every URI protocol except validated embedded raster data images. It strips resource-bearing markup/CSS and uses a unique owner-only temporary directory, removed in finally. It renders read-only snapshots of current WooCommerce orders and existing refunds, without issuing documents or creating payments/refunds.

Before every release review upstream security advisories and rerun `tests/local-pdf.php`, `tests/local-pdf-snapshot.php`, real WooCommerce snapshot/export checks and a visual PDF check. Revalidate when upstream versions change. The software's release owner is responsible for dependency updates; no automatic update or scheduler is bundled.
