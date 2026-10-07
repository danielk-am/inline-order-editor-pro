# Marketplace product page: draft text

Draft text for the WooCommerce.com product page that the free and paid submissions share. Not shipped in either ZIP. Paste each part into its field in the vendor dashboard once the account is approved.

- **Spelling:** English (UK), as Daniel asked on 7 October 2026. The Marketplace's content style guide asks for American English, so a reviewer may ask for a change. The words that would change are listed at the end.
- **Everything else follows the style guide:** the "for WooCommerce" name form, the Oxford comma, sentence-case headings, H2 and H3 only, no emojis, and no links to outside websites.
- **Name:** written with the current name. If the name changes, replace "Inline Order Editor" throughout.
- **Source of every claim:** the plugins' own files at free 1.0.3 and Pro 1.0.2. The table at the end says which file backs which claim.
- **Search terms:** none are targeted. There is no query data for these products yet, so the text is written from what the plugins do.

## Fields for the two submissions

| Field | Free submission | Paid submission |
| --- | --- | --- |
| Product name | Inline Order Editor for WooCommerce | Inline Order Editor Pro |
| Short description | Take phone orders on the WooCommerce order screen. Add any item as a new row, double-click to change a line, and add terms to order emails. | Saved terms, order discounts without a coupon, repeat orders, one-step mark as paid, and draft products from typed items, for Inline Order Editor. |
| Requirements | WooCommerce 10.0 or later, WordPress 6.8 or later, PHP 7.4 or later | The same, plus Inline Order Editor for WooCommerce 1.0.0 or later |
| Product icon | `design/marketplace-icon-160x160.png` | The same file |
| Highlight card colour | `#2563EB` | `#2563EB` |
| Demo URL | None yet | None yet |

Search title, 54 characters: `Inline Order Editor for WooCommerce: take phone orders`

Meta description, 139 characters: `Take phone orders on the WooCommerce order screen. Add any item as a new row, double-click to change a line, and add terms to order emails.`

## Product description

Paste from here to the next "Draft ends" line. The two lists use the Marketplace's tick-list classes.

---

Inline Order Editor is for shops that take orders by phone, over the counter, or by message, and type them into WooCommerce. It makes the order screen work like a line on an order pad: type the item, the price, and the quantity, press Enter, and the total is already right.

<ul class="wccom-tick-list-primary">
  <li>Add an item that is not in your catalogue, without creating a product first</li>
  <li>Change a price, a quantity, or a total with a double-click</li>
  <li>See the right total after every change, with no button to press</li>
  <li>Put your payment, delivery, or return terms in the customer's order email</li>
  <li>Keep the order screen you already use, with no new screen to learn</li>
</ul>

## What is Inline Order Editor for WooCommerce?

It is a WooCommerce extension that adds three things to the order screen under WooCommerce > Orders: an empty row for the next item, double-click editing of a line, and an Order terms box. It works with no setup. Activate it, open an order, and the empty row is at the bottom of the items.

## How to add an item while the customer is on the phone

An empty row waits at the bottom of the order's items. Type a name and choose what it is:

- **A product from your catalogue.** Matching products appear as you type, with their price and stock. Pick one and WooCommerce adds it as it normally would.
- **Something that is not in your catalogue.** Keep typing, give it a price and a quantity, and press Enter. It becomes a normal line on the order, with an optional note such as a colour or a size.
- **A delivery charge.** Choose "Add as a delivery charge" and type the amount. It is added as the order's shipping.

The row shows the line total as you type, and a fresh row is ready as soon as the line is added.

## How to change a line

Double-click a price, a quantity, or a total and type the new value. Enter saves it, Esc cancels, and Tab saves and moves to the next value. Nothing on the screen jumps, because the value is edited where it stands.

You can change the price of one item on one order, which the standard order screen does not offer. You can also rename an item you typed in, and change the name or amount of a fee or a delivery charge. On a keyboard, move to a value with Tab and press Enter. On a touch screen, tap it.

## Totals that are already right

After every change, the order's taxes and totals are calculated again, the way the Recalculate button does it. A short message at the bottom of the screen says what happened, for example: "Saved. The order total is now $65.00. It was $50.00."

If your shop's prices are entered with tax, the amounts you type are read with tax too. Type 10 and the customer pays 10. Each change also leaves a private order note with the old and the new value, under your name.

## Terms your customer can see

Each order has an Order terms box for payment, delivery, or return terms. Set default terms once and they are filled in on every new order you create in the admin, where you can still change them for that order.

The customer sees the terms in their order emails and on their order page. The terms are saved on the order itself, so changing the default later does not rewrite what an earlier customer was told. If you use PDF Invoices & Packing Slips for WooCommerce, the terms are printed on its invoices as well.

## A warning before a Draft order disappears

Draft is a status WooCommerce uses for unfinished checkouts, and it deletes Draft orders by itself about a day after their last change. If you pick Draft for an order you want to finish later, the extension tells you and suggests Pending payment, which keeps the order.

## What the paid version adds

Inline Order Editor Pro is a separate add-on for shops that take phone orders every day. It needs the free version.

<ul class="wccom-tick-list-secondary">
  <li><strong>Saved terms.</strong> Keep the terms you use often, such as one set for weddings and one for trade customers, and pick one on an order.</li>
  <li><strong>Order discount.</strong> Take an amount or a percentage off an order without a coupon. Type 10 or 10% and it is added as its own line.</li>
  <li><strong>Repeat order.</strong> For the customer who wants the same as last time. One action makes a new Pending payment order with the same customer, addresses, lines, delivery charge, and terms.</li>
  <li><strong>Mark as paid.</strong> Record a cash payment in one step. The order is set to paid the way WooCommerce does it, with a note of who recorded it.</li>
  <li><strong>Typed items as draft products.</strong> Sold the same typed item three times? Save it as a draft product, ready for you to finish and publish.</li>
</ul>

## Good to know

[box]Lines can be changed on orders WooCommerce treats as editable: Pending payment, On hold, and new orders. To change a paid order, set its status back to Pending payment first. An order that has a refund is left alone.[/box]

- Percentage and fixed product coupons apply to catalogue products only, so an item you typed in keeps its price. A fixed cart coupon is shared across every line. This is how WooCommerce applies coupons.
- An item you typed in has no stock, SKU, or weight.
- Changing a line recalculates the order's taxes, so a tax amount typed by hand is replaced by the calculated one.
- Neither version has been tested with WooCommerce Subscriptions, WooCommerce Bookings, Product Bundles, or tax services such as WooCommerce Tax.

---

Draft ends.

## FAQs

These go in the product page's FAQ section, one question per entry.

**Do I have to create a product for a one-off item?**
No. Type its name, price, and quantity in the empty row and press Enter. It is saved on the order as a normal line, and it shows on emails and invoices like any other line. Nothing is added to your catalogue.

**Can I change the price of one item on one order?**
Yes. Double-click the price and type the new one. The standard order screen only lets you edit a line's total, so the extension works the total out for you.

**Why can I not change a paid order?**
WooCommerce only allows changes to orders that are Pending payment or On hold, and the extension follows that rule, because a paid order's amount has usually been charged already. Set the status back to Pending payment, make the change, and settle any difference with the customer yourself.

**Do the prices I type include tax?**
They follow your shop. If your tax settings say prices are entered inclusive of tax, the amounts you type on an order include tax, and the price field says so when you point at it. Otherwise tax is added on top.

**Where do the order terms show?**
In the order emails WooCommerce sends, below the order details, and on the customer's order page. You can switch each place off under WooCommerce > Settings > General > Order terms.

**Does it work with High-Performance Order Storage?**
Yes. Both versions were built and tested with HPOS switched on, and with the older post-based order storage.

**Will it slow down my shop?**
It loads one script and one stylesheet, and only on the admin screen where a single order is edited. It adds nothing to the pages your customers see.

**Does it change the cart or the checkout?**
No. It works on the admin order screen only.

**Do I need the free version to use the paid one?**
Yes. Inline Order Editor Pro is an add-on and does nothing on its own.

**Does a repeated order charge the customer?**
No. It is a new Pending payment order with no payment details. Take payment the way you usually do.

**What happens if I deactivate it?**
The order screen goes back to how WooCommerce ships it. Lines you added stay on their orders, and saved terms stay in each order's data, but they are no longer shown in emails or on the order page.

**Where do I get help?**
Contact support from your WooCommerce.com account. Say which order status you were working on and what message you saw, and include your system status report.

## Product data

The Marketplace fills these in with AI first. Use this list to correct what it writes.

**Features**

- Add catalogue products, typed items, and delivery charges from one empty row
- Edit price, quantity, and total in place with a double-click
- Automatic tax and total recalculation after each change
- Order terms in emails, on the order page, and on PDF invoices
- Default terms for new admin orders
- Warning when the Draft status is chosen
- Private order note for every change
- Paid version: saved terms, order discounts, repeat orders, mark as paid, and draft products from typed items

**Search keywords (suggestions only, not measured):** phone orders, manual orders, edit order, order items, custom order item, order terms, order discount, repeat order, mark as paid

**Compatibility**

- High-Performance Order Storage: yes
- Post-based order storage: yes
- Cart and Checkout blocks: not affected, the extension works in the admin only
- PDF Invoices & Packing Slips for WooCommerce: order terms print on its invoices

## Gallery

The free plugin's five screenshots are 1280 x 860, which is above the Marketplace's minimum of 896 x 550. Suggested order and alt text:

1. `screenshot-3.png`, featured image. Alt text: An order with a catalogue product, a typed item, and a delivery charge, and a message saying what the last change did to the total.
2. `screenshot-1.png`. Alt text: The empty row at the bottom of an order's items, with a product suggested from the catalogue.
3. `screenshot-2.png`. Alt text: A quantity being changed in place on an order line.
4. `screenshot-4.png`. Alt text: The Order terms box on an order, filled in from the default terms.
5. `screenshot-5.png`. Alt text: The order terms in the customer's order email.

The paid version has no images yet.

## What is left open

- The name, the price, and the demo are not decided.
- No images exist for the paid version's features.
- The search keywords are guesses until there is real search data.
- "Will it slow down my shop?" is answered from where the files load. No speed measurement has been taken.

## Words that differ in American English

If a reviewer asks for American spelling, these are the only words to change in this file and the two documentation drafts: catalogue (catalog), colour (color), cheque (check), and licence as a noun (license). The plugin's own screens use "catalogue" and "colour" too.

## Where each claim comes from

| Claim | Backed by |
| --- | --- |
| The empty row, its three kinds of item, the note, the live line total | Free `readme.txt`; `assets/js/order-editor.js` strings ("Add an item: type its name", "From your catalogue", "Add as a new item", "Add as a delivery charge", "Note, such as colour or size (optional)", "Line total") |
| Suggestions show price and stock | Free `includes/class-ioefw-ajax.php` ("%s in stock", "Out of stock") |
| Double-click, Enter, Esc, Tab, keyboard, and touch | Free `readme.txt`; `order-editor.js` ("Double-click to change") |
| Price each can be changed; typed items, fees, and delivery charges can be renamed | Free `includes/class-ioefw-orders.php` (`update_item`, `item_values`) |
| Totals recalculated; the saved message; typed amounts include tax when shop prices do | Free `class-ioefw-orders.php` (`settle`, `amounts_include_tax`); `order-editor.js` ("Saved. The order total is now ... It was ...", "Price each, including tax") |
| A private order note per change | Free `class-ioefw-orders.php` note strings ("Price of ... changed from ... to ... each.") |
| Order terms box, default terms, where terms show, PDF invoices | Free `includes/class-ioefw-terms.php` and `class-ioefw-settings.php` ("Default terms", "In order emails", "On the customer's order page", "On invoices made by PDF Invoices & Packing Slips for WooCommerce") |
| Draft warning | Free `order-editor.js` ("WooCommerce deletes Draft orders by itself, about a day after their last change. To keep this order, choose Pending payment.") |
| Editable statuses, refunds, coupons, typed items, hand-typed tax | Free `readme.txt`, "Limits worth knowing"; `class-ioefw-orders.php` (`can_edit`) |
| The five paid features and their limits | Pro `readme.txt`; `includes/class-ioefwp-discount.php`, `class-ioefwp-order-actions.php`, `class-ioefwp-saved-terms.php` |
| HPOS and post-based storage | Both plugins' compatibility declarations; `tests/run.php` runs on both storages, recorded in `design/woo-marketplace-docs-notes.md` |
| One script and one stylesheet, on the single-order screen only | Free `includes/class-ioefw-plugin.php` (`setup_order_screen`); Plugin Check notes in the free repository's `design/wordpress-org-submission.md` |
| Not tested with Subscriptions, Bookings, Product Bundles, or tax services | Both `readme.txt` files |
| Requirements | Both plugin headers; Pro `README.md` |
