=== Inline Order Editor Pro ===
Contributors: danielkam1
Tags: woocommerce, phone orders, manual orders, order discount, repeat order
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Saved terms, order discounts, repeat orders, one-step "mark as paid" and draft products from typed items, for Inline Order Editor.

== Description ==

Inline Order Editor Pro adds five things to shops that take orders by phone with Inline Order Editor for WooCommerce. It needs that plugin, which is free, and WooCommerce.

= Saved terms =

Keep the terms you use often, such as one set for weddings and one for trade customers, under WooCommerce > Settings > General > Saved terms. On an order, pick one from "Use saved terms" and the Order terms box is filled in. You can still change the text for that order. Nothing is saved until you create or update the order.

= Order discount =

"Add discount" in the order's items takes an amount or a percentage off the order without a coupon. Type 10 or 10%, give it a name if you like, and it is added as its own line.

The discount is a fee line with an amount below zero, which WooCommerce keeps when coupons are applied again and prints on emails and invoices. A percentage is worked out once, from the items on the order at that moment, and the order note says so. A discount cannot be more than the items on the order.

= Repeat order =

For the customer who wants "the same as last time". Choose "Repeat this order as a new order" in the Order actions box and press Update. You get a new Pending payment order with the same customer, addresses, lines, delivery charge and terms, and you land on it ready to change.

Products from your catalogue are added at today's prices. Items you typed in are copied as they were. Payment details, coupons, fees and discounts are not copied, and a product that can no longer be bought is left out and named in the order note.

= Mark as paid =

"Mark as paid: Cash" in the Order actions box records the payment, sets the order to paid the way WooCommerce does, and notes who recorded it. Cash on delivery, direct bank transfer and cheque are offered too when you have switched them on under WooCommerce > Settings > Payments.

It is only offered on an order that is waiting for payment and is above zero, and an order cannot be marked as paid twice. WooCommerce sends its usual emails for a paid order.

= Typed items as draft products =

Sold the same typed item three times? "Save typed items as draft products" makes a draft product for each one, with its name and price. The drafts wait under Products for you to finish and publish. Nothing is published for you, and the order's lines stay as they are.

= Limits worth knowing =

* The discount, like every change to an order's lines, works on orders WooCommerce treats as editable: Pending payment, On hold, and new orders.
* A percentage discount is not updated when you change the order afterwards. Delete the line and add it again.
* Repeat order copies lines, delivery charges and terms. It does not copy fees, discounts, coupons, downloads or subscriptions.
* Tested on WordPress 7.1.2 with WooCommerce 11.1.2 and High-Performance Order Storage. Not tested with subscriptions, bookings, product bundles or tax services.

== Installation ==

1. Install and activate WooCommerce and Inline Order Editor for WooCommerce.
2. Upload this plugin's ZIP under Plugins > Add Plugin > Upload Plugin, and activate it.
3. Open an order. "Add discount" is in the items box, the new entries are in the Order actions box, and "Use saved terms" is under the Order terms box once you have saved some.

== Frequently Asked Questions ==

= Does it work without Inline Order Editor for WooCommerce? =

No. It is an add-on and does nothing on its own.

= Why is the discount a line and not a coupon? =

A coupon has to exist in your shop first, and WooCommerce applies it by its own rules. A discount line is exactly the amount you agreed on the phone, shown to the customer as its own line.

= Does a repeated order charge the customer? =

No. It is a new Pending payment order with no payment details. Take payment the way you usually do.

= Can I undo "Mark as paid"? =

Change the order's status back, as you would for any order. The order note stays, so there is a record of what happened.

= What happens to the draft products if I delete the order? =

They stay under Products as drafts until you publish or delete them.

== Changelog ==

= 1.0.0 =
* First release.
