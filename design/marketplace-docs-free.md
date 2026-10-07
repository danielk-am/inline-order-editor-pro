# Documentation draft: Inline Order Editor for WooCommerce

Draft of the documentation page for WooCommerce.com. Not shipped in the ZIP. Written for shop owners and staff who take orders by phone, in English (UK) as Daniel asked. Checked against the plugin's files at version 1.0.3 on 7 October 2026.

Before pasting it into the vendor dashboard:

- Replace the installation section with the Marketplace's own installation pattern.
- Add the screenshots where marked. Each mark gives the image to use and its alt text.
- The page title is the product name, so the draft starts at H2.

---

Inline Order Editor for WooCommerce helps you take orders by phone, over the counter, or by message. It adds three things to the order screen you already use under WooCommerce > Orders:

- An empty row that adds the next item: a product from your catalogue, something that is not in it, or a delivery charge.
- Double-click editing of a line's price, quantity, and total, right where the value stands.
- An Order terms box, whose text the customer sees in their order emails and on their order page.

After every change, the order's taxes and totals are worked out again for you.

## Installation

Use the Marketplace's installation pattern here.

## Requirements

- WooCommerce 10.0 or later
- WordPress 6.8 or later
- PHP 7.4 or later

There is nothing to set up. Once the extension is active, open any order that is Pending payment or On hold, or go to WooCommerce > Orders > Add new order. The empty row is at the bottom of the order's items.

## Add an item to an order

Click the empty row at the bottom of the items and start typing a name. After two letters, a short list of suggestions appears.

Screenshot: `screenshot-1.png`. Alt text: The empty row at the bottom of an order's items, with a product suggested from the catalogue.

### Add a product from your catalogue

1. Type part of the product's name. Matching products appear in the list, each with its price and stock.
2. Pick the product with the mouse, or with the arrow keys and Enter. The row is marked "From your catalogue", the product's price is filled in, and the cursor moves to the quantity.
3. Change the quantity if you need to, then press Enter or select **Add**.

WooCommerce adds the product the same way its own Add product(s) button does, so stock and your other extensions behave as usual. For a product with variations, the list shows the variations, since those are what a customer can order.

### Add an item that is not in your catalogue

1. Type the item's name. Press Enter, or choose **Add "your item" as a new item** from the list. The cursor moves to the price.
2. Type the price each and the quantity.
3. Add a note if it helps, such as a colour or a size. The note is optional.
4. If your shop charges tax, choose the tax for this item.
5. Press Enter or select **Add**.

The item becomes a normal line on the order. It shows on emails and invoices like any other line, and nothing is added to your catalogue.

### Add a delivery charge

1. Type a name for the charge, such as Courier.
2. Choose **Add "Courier" as a delivery charge** from the list. The row is marked "Delivery charge".
3. Type the amount and press Enter.

The charge is added as the order's shipping.

### What happens when you add a line

The row shows the line total as you type. When the line is added, a fresh empty row is ready for the next item, and a message at the bottom of the screen says what happened. On a new order, the message also reminds you to select **Create** to keep the order.

## Change a line

Screenshot: `screenshot-2.png`. Alt text: A quantity being changed in place on an order line.

1. Double-click the price, the quantity, or the total of a line.
2. Type the new value.
3. Press Enter to save. Press Esc to cancel. Press Tab to save and move to the next value.

On a keyboard, move to a value with Tab and press Enter to change it. On a touch screen, tap the value.

### What you can change

| Line | What you can change |
| --- | --- |
| A product from your catalogue | Price each, quantity, and total |
| An item you typed in | Name, price each, quantity, and total |
| A fee | Name and amount |
| A delivery charge | Name and amount |

To remove a line, use its delete button, as you would without the extension.

### Prices and tax

Amounts you type follow your shop's tax setting. If WooCommerce > Settings > Tax says prices are entered inclusive of tax, the amounts you type on an order include tax, and the price field says "Price each, including tax". Otherwise tax is added on top.

### Orders with a coupon

On an order that has a coupon, a line's total follows the coupon, so you change the price and the quantity and the total is worked out for you. Each change applies the order's coupons again.

Percentage and fixed product coupons apply to catalogue products only, so an item you typed in keeps its price. A fixed cart coupon is shared across every line. This is how WooCommerce applies coupons.

## Totals and messages

After every change, the extension recalculates the order's taxes and totals the way the Recalculate button does. A message at the bottom of the screen confirms it, for example: "Saved. The order total is now $65.00. It was $50.00."

Screenshot: `screenshot-3.png`. Alt text: An order with a catalogue product, a typed item, and a delivery charge, and a message saying what the last change did to the total.

Each change also leaves a private order note with the old and the new value, under the name of the person who made it. Customers do not see these notes.

## Order terms

The Order terms box holds payment, delivery, or return terms for one order.

Screenshot: `screenshot-4.png`. Alt text: The Order terms box on an order, filled in from the default terms.

### Add terms to an order

1. Open the order and find the **Order terms** box.
2. Type the terms, or select **Use the default terms** to fill them in.
3. Select **Create** or **Update** to save the order. The terms are saved with it.

Each order keeps its own copy of its terms. Changing the default later does not rewrite what an earlier customer was told.

### Set default terms

1. Go to WooCommerce > Settings > General and scroll to **Order terms**.
2. Type your usual terms under **Default terms**.
3. Save your changes.

The default terms are filled in on each new order you create in the admin. You can still change them on that order. Leave the setting empty to start every order without terms.

### Choose where the terms show

Screenshot: `screenshot-5.png`. Alt text: The order terms in the customer's order email.

| Setting | Default | What it does |
| --- | --- | --- |
| Default terms | Empty | The terms filled in on each new order you create in the admin. |
| Heading | Terms | The heading shown above the terms. |
| Show terms: In order emails | On | Adds the terms to the order emails WooCommerce sends, below the order details. |
| Show terms: On the customer's order page | On | Shows the terms on the customer's order page and on the order received page. |
| Show terms: On invoices made by PDF Invoices & Packing Slips for WooCommerce | On | Prints the terms on that extension's invoices, when you use it. |

An order with no terms shows nothing in any of these places.

## Draft orders

Draft is a status WooCommerce uses for checkouts that were started and not finished. It deletes Draft orders by itself about a day after their last change.

When you choose Draft as an order's status, the extension shows a warning under the status field and suggests Pending payment, which keeps the order. Use Pending payment for any order you want to come back to.

## Which orders can be changed

Lines can be added and changed on orders WooCommerce treats as editable:

- Pending payment
- On hold
- New orders that have not been created yet

To change a paid order, set its status back to Pending payment, make the change, and settle any difference with the customer yourself. An order that has a refund cannot be changed with the extension.

## Good to know

- An item you typed in has no stock, SKU, or weight.
- In Analytics > Products, typed items have no product to be listed under, so they appear together in one row marked "(Deleted)".
- Changing a line recalculates the order's taxes. A tax amount you typed by hand with the pencil is replaced by the calculated one.
- The extension has not been tested with WooCommerce Subscriptions, WooCommerce Bookings, Product Bundles, or tax services such as WooCommerce Tax.

## Troubleshooting

### The empty row is not there

The order is not one WooCommerce lets you edit, or it has a refund. Check the status: it needs to be Pending payment or On hold. See "Which orders can be changed".

### No products are suggested

Suggestions start after you have typed two letters. If the product still does not appear, check that it is published. You can always add the item by typing its name, price, and quantity.

### A message says the order was changed somewhere else

The full message is: "This order was changed somewhere else, so nothing was saved. The lines below are now up to date. Please make your change again." Someone else, or another tab, changed the order while you had it open. Nothing was lost. The screen now shows the latest lines, so make your change again.

### A message says the page has been open for a while

Reload the page, then try again. WordPress asks for this after a page has been open for many hours.

### A message says "That did not save"

Check your internet connection and try again. If it keeps happening, reload the page.

### A message says that part of the line cannot be changed here

Some values are fixed: the name of a catalogue product, and a line's total on an order that has a coupon. Change the price or the quantity instead.

### A message says you are not allowed to change orders

Your user account cannot edit orders. Ask the shop's owner to give you the Shop manager role.

### The terms are not in the customer's email

Check these in order:

1. The order has terms in its Order terms box, and you selected **Create** or **Update** after typing them.
2. **In order emails** is ticked under WooCommerce > Settings > General > Order terms.
3. The email was sent after the terms were saved. Send the order details again from the Order actions box to check.

### An order saved as Draft has disappeared

WooCommerce clears Draft orders away about a day after their last change. The order cannot be brought back. Use Pending payment for orders you want to keep.

## For developers

- An order's terms are stored in the order meta key `_ioefw_order_terms`. `ioefw_get_order_terms( $order )` returns them as plain text, ready for a PDF or email template.
- An item that was typed in is a normal product line with no product ID, marked with the hidden item meta `_ioefw_custom`.
- Changes to existing lines are saved through WooCommerce's own `wc_save_order_items()`, so the hooks that fire for the order screen's Save button fire here too.
- Filters: `ioefw_can_edit_order`, `ioefw_editable_fields`, `ioefw_custom_item_args`, `ioefw_amounts_include_tax`, `ioefw_order_terms`, `ioefw_show_terms_in_email`, `ioefw_pdf_document_types`, `ioefw_product_search_limit`.
- Actions: `ioefw_loaded`, `ioefw_custom_item_added`, `ioefw_delivery_added`, `ioefw_item_updated`, `ioefw_after_recalculate`, `ioefw_terms_saved`, `ioefw_terms_box_actions`.
- The extension makes no requests to other sites.

## Deactivating and uninstalling

When you deactivate the extension, the order screen goes back to how WooCommerce ships it. Lines you added stay on their orders. Saved terms stay in each order's data, but they are no longer shown in emails or on the order page.

Deleting the extension removes its five settings. It does not change any order.

## Getting help

Contact support from your WooCommerce.com account. It helps to say which order status you were working on, what you typed, and the exact message you saw.

---

Draft ends.

## Checked against

| Part | Backed by |
| --- | --- |
| Suggestions after two letters, price and stock, variations | `includes/class-ioefw-ajax.php`; `assets/js/order-editor.js` (`choose()` and the Enter key handling for the empty row) |
| Labels and messages in quotation marks or bold | Strings in `assets/js/order-editor.js`, `includes/class-ioefw-orders.php`, `class-ioefw-terms.php`, and `class-ioefw-settings.php` |
| What can be changed on each kind of line | `includes/class-ioefw-orders.php`, `item_values()` and `update_item()` |
| Settings and their defaults | `includes/class-ioefw-settings.php`; the heading falls back to "Terms" in `class-ioefw-terms.php` |
| Editable statuses and refunds | `includes/class-ioefw-orders.php`, `can_edit()` |
| Uninstall removes five settings | `uninstall.php`; `tests/run.php` |

Not checked: the "Shop manager role" advice assumes the shop uses WooCommerce's standard roles. The message about a page open for a while appears when WordPress rejects an old security token. The draft says "many hours" without a measured figure.
