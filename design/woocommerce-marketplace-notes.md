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

- **PHP compatibility** (PHPCompatibility, the develop branch QIT's documentation names, PHP 7.4 and later): two `trim()` calls here and five in the free plugin rely on PHP's default characters, which change in PHP 8.6. Both plugins now name the characters. The rule reports this only when the range checked reaches PHP 8.6, which is not released yet: for PHP 7.4 to 8.5, version 1.0.1 was clean.
- **PHPStan** at level 2 with WordPress and WooCommerce stubs: eight messages here and 19 in the free plugin, all for methods called on a line whose class PHPStan could not see. The code now checks the line's class first. Level 0, the level QIT's page says it runs, was already clean.
- **Security** (PHPCS `WordPress.Security` and `WordPress.DB`, Semgrep's public PHP, WordPress, JavaScript and secrets packs, gitleaks on the package and the git history): nothing found, before or after.
- **Validation** (readme, headers, no `Woo:` header, HPOS and blocks declarations) and a **malware pattern** scan: nothing found.

After the changes, on version 1.0.2 of both:

- All of the above: nothing found.
- `tests/run.php`: 64 checks, 0 failed, on WordPress 7.1.3 with WooCommerce 11.1.2 and PHP 8.5, with HPOS and with post-based order storage, and on WordPress 6.8.10 with WooCommerce 10.0.6 and PHP 7.4.33 with HPOS.
- Plugin Check 2.1.0: no errors, and the same one warning about the required plugin.
- An activation run on WordPress 7.1.3 with WooCommerce 11.1.2 and PHP 8.5, and on WordPress 6.8.11 with WooCommerce 10.0.6 and PHP 7.4.33, both with HPOS: admin pages load, a product and an administrator's order are created, a guest adds to cart and checks out with cash on delivery, both plugins are switched off and on again, and the debug log, read from the store's first boot, has no line that a control store without the plugins does not also have. WooCommerce logs a notice and some database errors of its own on these Playground stores. The steps follow QIT's Activation page.

Not covered by any of this: QIT's WooCommerce end-to-end and REST API suites with the add-on active, and its lookup in the WPScan vulnerability database.

## Vendor account and first submission, read on 7 October 2026

Sources: woocommerce.com/partners, and "Getting started", "Submitting your product", "Monetization expectations" and "Product page content and assets" under developer.woocommerce.com/docs/woo-marketplace. The Woo trademark guidelines at woocommerce.com/trademark-guidelines.

**The account.** "Become a partner" on woocommerce.com/partners opens woocommerce.com/marketplace-seller-application. It first asks to connect a WordPress.com account, then for a partner profile about the business and what it sells. The application has to be approved before a product can be submitted. Connecting the account, the profile and the vendor agreement are the account owner's to do.

**Where QIT first runs.** The QIT command line needs an account that already has a listed extension, so it cannot be used before the first product is accepted. The submission itself runs the tests: after the ZIP is uploaded under Submissions > Submit Product, the results appear on the submission, a failed test has a View link, and Replace uploads a corrected ZIP and starts the tests again. A submission has to pass API, end-to-end, activation, security, PHP compatibility, malware and validation.

**Two submissions.** A free plus paid pair goes in as two submissions, one for each, and the Marketplace joins them on one product page. A free plugin with no paid path is not accepted alone, so the free plugin goes in with this add-on, not before it.

**What the submission form asks for.** The product ZIP, business details, and testing instructions.

Draft testing instructions, free plugin:

1. Activate WooCommerce and Inline Order Editor for WooCommerce. No setup is needed.
2. Go to WooCommerce > Orders > Add new order. An empty row is at the bottom of the items.
3. Type part of a product's name and pick the suggestion. Then type a name that is not a product, a price and a quantity, and press Enter. Then type a name, choose "Add as a delivery charge", and type an amount.
4. Double-click a quantity, type a new one and press Enter. The total updates and a message at the bottom of the screen says what changed. Do the same with a price.
5. Type terms in the Order terms box, click Create, and send the order details to the customer from Order actions. The terms are in the email, and on the customer's order page.
6. Default terms and where they show are under WooCommerce > Settings > General > Order terms.

Draft testing instructions, add-on (with the free plugin active):

1. WooCommerce > Settings > General > Saved terms: add two sets of terms and save. On an order, choose one under "Use saved terms".
2. On an order with items, click "Add discount", type 10% and add it. It appears as its own line and the total drops.
3. In Order actions choose "Repeat this order as a new order" and click Update. A new Pending payment order opens with the same customer, lines, delivery charge and terms.
4. In Order actions choose "Mark as paid: Cash" and click Update. The order is set to paid the way WooCommerce does it, and an order note records who did it.
5. On an order with a typed item, choose "Save typed items as draft products" in Order actions and click Update. A draft for each typed item appears under Products.

**Still to decide or settle before submitting.**

- **Name.** The naming rules ask for one to five words, a name that is not a description of the product, and no Woo marks inside the name itself. The trademark guidelines allow "for WooCommerce" after a distinctive product name. "Inline Order Editor" is descriptive, the same concern WordPress.org's form raises. "Order Jotter" is the fallback already checked for the free plugin.
- **Price**, the support address, and the business details. The suggested price has to be equal to or less than the price anywhere else.
- **Popular extensions.** The submission page names WooCommerce Bookings, WooCommerce Subscriptions, Product Add-Ons, Product Bundles, Min/Max Quantities and Shipment Tracking, and says a product that breaks one of them may be rejected. Neither plugin has been tested with any of them. They are paid extensions, so testing needs copies.
- **Outside links.** The business review excludes affiliate links and links to upsell products outside the Marketplace. The free plugin's `readme.txt` has a donate link to danielk.am. Ask, or leave it out of the Marketplace build.
- **Accessibility.** Submissions have to meet the WordPress accessibility coding standards. The order screen additions were built for keyboard and screen reader use and checked with scripted events. No separate accessibility audit has been done.
- **The dependency header.** This add-on's `Requires Plugins` names the free plugin's WordPress.org folder. Whether the free plugin keeps that folder name when the Marketplace packages it could not be confirmed.

## How the free and paid versions fit the rules

The Marketplace takes a free plus paid pair as two submissions, and paid features must not sit inside the free one. They do not: the free plugin has no locked feature and no mention of this add-on, and this add-on is a separate plugin that starts from the free plugin's `ioefw_loaded` action. WordPress.org allows a paid add-on hosted elsewhere on the same terms.

Whether the Marketplace accepts a paid add-on whose free part lives on WordPress.org, instead of both being listed on the Marketplace, could not be confirmed from the public pages. Ask in the vendor application.
