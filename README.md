# Inline Order Editor Pro

An add-on for [Inline Order Editor for WooCommerce](https://github.com/danielk-am/inline-order-editor-for-woocommerce), for shops that take orders by phone. It needs that plugin and WooCommerce.

## What it adds

- **Saved terms.** Named terms texts under WooCommerce > Settings > General. Pick one on an order to fill in its Order terms box.
- **Order discount.** "Add discount" in the items box takes an amount or a percentage off the order, as its own line, without a coupon.
- **Repeat order.** An Order actions entry that makes a new Pending payment order with the same customer, lines, delivery charge and terms, with catalogue products at today's prices.
- **Mark as paid.** Order actions entries for cash and for the offline payment methods the shop has switched on.
- **Typed items as draft products.** An Order actions entry that makes a draft product from each typed item. Nothing is published.

## How it is built

It starts on the free plugin's `ioefw_loaded` action and does its work through that plugin's public methods: `IOEFW_Orders::can_edit()`, `add_custom_item()`, `settle()`, `IOEFW_Terms::set()` and `get()`, and `IOEFW_Ajax::fragments()`. On the screen it draws through `window.ioefw.draw()` and `window.ioefw.notice()`. Nothing from the free plugin is copied or switched off.

- The discount is a fee line with an amount below zero. WooCommerce keeps it when coupons are applied again and prints it on emails and invoices. A percentage is worked out once and the order note says so.
- A repeat copies no payment details, coupons, fees or discounts. A line whose product was deleted, or can no longer be bought, is left out and named in the order note.
- Mark as paid is offered only on an order that is waiting for payment and above zero, and never twice. It calls `payment_complete()`, so WooCommerce's own emails and stock rules apply.
- There are no licence checks, update code or links out.

## Develop and test

```bash
bin/build.sh --zip
```

Start the test store from the free plugin's repository with this add-on mounted as well, then open `/wp-admin/admin-post.php?action=ioefw_dev&script=prorun` to run `tests/run.php`. The checks cover each feature and each of its limits, with tax added on top and tax included.

`php tests/package.php build/inline-order-editor-pro` checks the built package without a store: `changelog.txt` is there in the WooCommerce.com Marketplace format, and its newest entry, the plugin header and the readme's Stable tag carry the same version.

## Requirements

WordPress 6.8 or later, WooCommerce 10.0 or later, PHP 7.4 or later, and Inline Order Editor for WooCommerce 1.0.0 or later. Version 1.0.2 was tested with Inline Order Editor 1.0.2 on WordPress 7.1.3 with WooCommerce 11.1.2 and PHP 8.5, and on WordPress 6.8.10 with WooCommerce 10.0.6 and PHP 7.4. Its messages show as toasts with Inline Order Editor 1.0.1 or later.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).
