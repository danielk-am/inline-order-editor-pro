# WooCommerce.com Marketplace notes

Working notes for submitting Inline Order Editor Pro. Not shipped in the ZIP. The vendor application, the agreement and the product submission are the account owner's to do.

Rules read on 6 October 2026: "Submitting your product to the Woo Marketplace", the monetisation, support and product page guidelines under developer.woocommerce.com/docs/woo-marketplace, the QIT managed tests pages, and the vendor agreement. The Woo pages carry no dates, so read them again before submitting.

## What is ready

- `build/inline-order-editor-pro.zip`, built with `bin/build.sh --zip`. One top-level folder, runtime files only.
- Headers the Marketplace validation reads: Requires PHP, Requires at least, Tested up to (in `readme.txt`), WC requires at least, WC tested up to, License. There is no `Woo:` header: the Marketplace adds it when the product is deployed.
- HPOS compatibility is declared, and every order is read and written with `WC_Order` methods. Cart and Checkout blocks compatibility is declared: the add-on touches neither.
- `changelog.txt` in the Marketplace format.
- No licence checks, no update code, no upsell or affiliate links, no admin notices.
- `tests/run.php`: 64 checks, 0 failed, on WordPress 7.1.2 with WooCommerce 11.1.2 and PHP 8.5, and again on WordPress 6.8.10 with WooCommerce 10.0.6 and PHP 7.4.33. Both with HPOS.
- Plugin Check 2.1.0: no errors, one warning. The warning says the required plugin `inline-order-editor-for-woocommerce` is not in the WordPress.org directory. It clears once the free plugin is approved there.
- `design/marketplace-icon-160x160.png`, drawn from the free plugin's icon.

## What has not been done

- **QIT.** The Marketplace runs its Quality Insights Toolkit tests on submission: activation, security, PHP compatibility, malware, validation, API and end-to-end. None of them has been run. They need a WooCommerce.com vendor account with a listed extension. The local checks below use open tools and are not a QIT pass. Neither is a clean Plugin Check.
- **Real input on the lower stack and on post-based storage.** The add-on's screens were clicked through by hand on the newer stack with HPOS. Elsewhere they were driven with scripted events only.

## Checked again on 6 October 2026, with Inline Order Editor 1.0.1

The free plugin moved its messages to toasts in 1.0.1. The add-on needed no change: it reports through the free plugin's `window.ioefw.notice()`, so "Discount added." and its errors now show as toasts too.

- `tests/run.php`: 64 checks, 0 failed, on WordPress 7.1.2 with WooCommerce 11.1.2 and PHP 8.5, with HPOS and with post-based order storage, and on WordPress 6.8.10 with WooCommerce 10.0.6 and PHP 7.4.33 with HPOS.
- Version 1.0.1 of the add-on followed the same day: its `readme.txt` now says post-based order storage was tested, and the discount toast gives the new order total.
- **Product page.** A name of one to five words, a short description, the gallery images at 896 x 550 or larger, documentation and a support address are entered in the vendor dashboard.
- **Price.** The agreement asks for the same price as anywhere else the product is sold.

## Local checks before QIT, 7 October 2026, version 1.0.2

QIT itself could not run: `qit connect` needs a vendor account, and none exists yet. The same kinds of checks were run with open tools on the 1.0.1 build of this add-on and of the free plugin. QIT's own rule sets are not published, so a clean result here does not promise a clean QIT result.

What 1.0.1 showed, and what 1.0.2 changes:

- **PHP compatibility** (PHPCompatibility, the develop branch QIT's documentation names, PHP 7.4 and later): two `trim()` calls here and five in the free plugin rely on PHP's default characters, which change in PHP 8.6. Both plugins now name the characters.
- **PHPStan** at level 2 with WordPress and WooCommerce stubs: eight messages here and 19 in the free plugin, all for methods called on a line whose class PHPStan could not see. The code now checks the line's class first. Level 0, the level QIT's page says it runs, was already clean.
- **Security** (PHPCS `WordPress.Security` and `WordPress.DB`, Semgrep's public PHP, WordPress, JavaScript and secrets packs, gitleaks on the package and the git history): nothing found, before or after.
- **Validation** (readme, headers, no `Woo:` header, HPOS and blocks declarations) and a **malware pattern** scan: nothing found.

After the changes, on version 1.0.2 of both:

- All of the above: nothing found.
- `tests/run.php`: 64 checks, 0 failed, on WordPress 7.1.3 with WooCommerce 11.1.2 and PHP 8.5, with HPOS and with post-based order storage, and on WordPress 6.8.10 with WooCommerce 10.0.6 and PHP 7.4.33 with HPOS.
- Plugin Check 2.1.0: no errors, and the same one warning about the required plugin.
- An activation run on each of those three stores, following the steps on QIT's Activation page: admin pages load, a product and an administrator's order are created, a guest adds to cart and checks out with cash on delivery, both plugins are switched off and on again, and the debug log has no PHP notice, warning or error.

Not covered by any of this: QIT's WooCommerce end-to-end and REST API suites with the add-on active, and its lookup in the WPScan vulnerability database.

## How the free and paid versions fit the rules

The Marketplace takes a free plus paid pair as two submissions, and paid features must not sit inside the free one. They do not: the free plugin has no locked feature and no mention of this add-on, and this add-on is a separate plugin that starts from the free plugin's `ioefw_loaded` action. WordPress.org allows a paid add-on hosted elsewhere on the same terms.

Whether the Marketplace accepts a paid add-on whose free part lives on WordPress.org, instead of both being listed on the Marketplace, could not be confirmed from the public pages. Ask in the vendor application.
