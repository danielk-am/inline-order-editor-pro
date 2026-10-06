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

- **QIT.** The Marketplace runs its Quality Insights Toolkit tests on submission: activation, security, PHP compatibility, malware, validation, API and end-to-end. None of them has been run here. They need a vendor account. A clean Plugin Check is not a QIT pass.
- **Post-based order storage.** The add-on's checks ran with HPOS only. The free plugin's ran with both.
- **The screens on the lower stack.** The checks passed there, but the add-on's screens were only clicked through on the newer stack.
- **Product page.** A name of one to five words, a short description, the gallery images at 896 x 550 or larger, documentation and a support address are entered in the vendor dashboard.
- **Price.** The agreement asks for the same price as anywhere else the product is sold.

## How the free and paid versions fit the rules

The Marketplace takes a free plus paid pair as two submissions, and paid features must not sit inside the free one. They do not: the free plugin has no locked feature and no mention of this add-on, and this add-on is a separate plugin that starts from the free plugin's `ioefw_loaded` action. WordPress.org allows a paid add-on hosted elsewhere on the same terms.

Whether the Marketplace accepts a paid add-on whose free part lives on WordPress.org, instead of both being listed on the Marketplace, could not be confirmed from the public pages. Ask in the vendor application.
