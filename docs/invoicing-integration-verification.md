# Local invoicing integration verification

Local candidate: Inline Order Editor Pro 1.1.0-alpha. Prepared 14–15 October 2026. No commit, push, release, Marketplace upload, or production deployment.

## Scope and preserved work

The existing uncommitted 1.0.3 discount/layout, gallery, screenshots, tests, and documentation changes were read and preserved. Added the module under `modules/invoicing/`, a bootstrap under `includes/class-ioefwp-invoicing.php`, and CLI activation-order tests. Only version/description/header/tested-matrix/doc hunks required by the new alpha were adjusted in existing files. No base or standalone Alkamy source was modified.

Current Alkamy includes/assets were copied after the metadata idempotence and sanitation changes settled. Translation domain changes to `inline-order-editor-pro`; menu identifier `alkamy-invoicing`, PHP class identities, hook names, order meta/options, document records, service connection, and asset selectors remain compatible. The module entrypoint has no standalone plugin header. `ALKAMY_INVOICING_FILE` anchors assets under the module directory.

## Checks executed

- Loader test was observed failing before implementation because bundled invoicing was absent.
- `php tests/invoicing-loader.php normal`: module loads after all plugins; document action once; repeated bootstrap once; asset anchor resolves.
- `php tests/invoicing-loader.php missing-base`: no bundled classes or document mutation hooks.
- `php tests/invoicing-loader.php standalone`: standalone classes included after Pro win ownership; no bundled document init; explanatory notice once.
- `bin/build.sh --zip`: built exact local candidate.
- `php tests/package.php build/inline-order-editor-pro`: all eight package/changelog checks passed. Alpha suffix supported as a local prerelease check, not a Marketplace eligibility claim.
- Shared WordPress packaging script: root, dev exclusions, version, and domain passed. Docs/tests/design omitted from install ZIP.
- PHP syntax checks passed for every exact-package PHP file; JavaScript syntax check passed for bundled admin.js.
- Plugin Check 2.1.0 PHP ruleset through its bundled PHPCS, exact package: zero errors and zero warnings. See task output `pro-package-static-check.json`. This is the static PHP subset, not a complete runtime Plugin Check or QIT result.

## Not established

Fresh combined WordPress activation, browser invoice widgets, existing Pro operational regressions, HPOS/legacy matrix, WooPayments/Stripe live metadata transport, service setup, negative discount financial mapping, already-paid order invoices, multilingual/long PDFs, and production service policies remain to be tested or implemented. Historical 1.0.3 test results do not apply automatically to 1.1.0-alpha.

When standalone Alkamy is active, its own module and translation/asset path stay responsible for documents. Pro's old order workflows continue their existing base-plugin hook. Operators should disable standalone Alkamy when selecting the bundled Pro implementation; credentials/documents are kept, not migrated or deleted.
