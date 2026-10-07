# Documentation draft: Inline Order Editor Pro

Draft of the documentation page for WooCommerce.com. Not shipped in the ZIP. Written for shop owners and staff who take orders by phone, in English (UK) as Daniel asked. Checked against the add-on's files at version 1.0.2 on 7 October 2026.

Before pasting it into the vendor dashboard:

- Replace the installation section with the Marketplace's own installation pattern.
- Add the screenshots where marked. Each mark gives the image in `design/gallery/` and its alt text.
- The page title is the product name, so the draft starts at H2.

---

Inline Order Editor Pro adds five things for shops that take phone orders every day:

- **Saved terms.** Keep the terms you use often and pick one on an order.
- **Order discount.** Take an amount or a percentage off an order without a coupon.
- **Repeat order.** Make a new order from an earlier one, for the customer who wants the same as last time.
- **Mark as paid.** Record a cash payment in one step.
- **Typed items as draft products.** Turn items you typed in on an order into draft products.

It is an add-on for Inline Order Editor for WooCommerce and does nothing on its own.

## Installation

Use the Marketplace's installation pattern here.

## Requirements

- Inline Order Editor for WooCommerce 1.0.0 or later, installed and active
- WooCommerce 10.0 or later
- WordPress 6.8 or later
- PHP 7.4 or later

There is nothing to set up. Once both extensions are active, open an order:

- **Add discount** is in the order's items box.
- The new actions are in the **Order actions** box.
- **Use saved terms** appears under the Order terms box once you have saved some terms.

## Saved terms

Saved terms are sets of terms you use often, such as one for weddings and one for trade customers.

### Save a set of terms

1. Go to WooCommerce > Settings > General and scroll to **Saved terms**.
2. Type a name, for example Wedding orders. The name is for you. Customers do not see it.
3. Type the terms.
4. Select **Add another** for each further set, or **Remove** to delete one.
5. Save your changes.

Screenshot: `gallery-4.png`. Alt text: The Saved terms setting with two sets filled in, Wedding orders and Trade customers.

### Use saved terms on an order

1. Open the order and find the **Order terms** box.
2. Choose a set from **Use saved terms**. The box is filled in with its text.
3. Change the text for this order if you need to.
4. Select **Create** or **Update**. Nothing is saved until you do.

Screenshot: `gallery-3.png`. Alt text: The Order terms box on an order, filled in from a saved set of terms, with the Use saved terms menu beneath it.

Each order keeps its own copy of its terms. Changing or removing a saved set later does not change orders that already used it.

## Order discount

Use an order discount when you have agreed a reduction with the customer and do not want to create a coupon for it.

### Add a discount

1. In the order's items box, select **Add discount**.
2. Type an amount, such as 10, or a percentage, such as 10%.
3. Give the discount a name if you like. Without one, it is called Discount.
4. Select **Take it off**.

Screenshot: `gallery-1.png`. Alt text: The Add discount form on an order, with 10% typed in and the name Regular customer.

The discount is added as its own line, and a message says what the order total is now and what it was. The customer sees the line on their emails and invoices.

Screenshot: `gallery-2.png`. Alt text: An order with a discount added as its own line, and a message saying what the discount did to the order total.

### How the discount is worked out

- An amount is taken off as typed. If your shop's prices are entered with tax, the amount is read with tax too.
- A percentage is worked out once, from the items on the order at that moment. The order note says what it came to. If you change the order afterwards, the discount does not follow. Delete the line and add it again.
- A discount cannot be more than the items on the order, and a percentage cannot be more than 100%.
- The discount stays in place when the order's coupons are applied again.

### Remove a discount

Delete its line with the line's delete button, as you would any fee.

## Repeat order

1. Open the earlier order.
2. In the **Order actions** box, choose **Repeat this order as a new order**.
3. Select **Update**.

A new Pending payment order opens, ready to change. It has the same customer, addresses, lines, delivery charge, and terms.

Screenshot: `gallery-5.png`. Alt text: A new order made by repeating an earlier one, with the same lines and a note saying which order it came from.

| From the earlier order | In the new order |
| --- | --- |
| Products from your catalogue | Added at today's prices |
| Items you typed in | Copied as they were, with their notes |
| Delivery charges | Copied |
| Order terms | Copied |
| Payment details, coupons, fees, and discounts | Not copied |
| A product that can no longer be bought | Left out, and named in the new order's note |

The earlier order gets a note saying which order it was repeated as. The new order does not charge the customer. Take payment the way you usually do.

## Mark as paid

1. Open an order that is waiting for payment.
2. In the **Order actions** box, choose **Mark as paid: Cash**.
3. Select **Update**.

The order is set to paid the way WooCommerce does it, so WooCommerce sends its usual emails and applies its stock rules. An order note records how it was paid and who recorded it.

Screenshot: `gallery-7.png`. Alt text: An order marked as paid by cash, with the paid amount under the totals and a note of who recorded it.

If you have switched on cash on delivery, direct bank transfer, or cheque payments under WooCommerce > Settings > Payments, each one is offered as a "Mark as paid" action too.

The action is only offered on an order that is waiting for payment and is above zero. An order cannot be marked as paid twice.

### Undo it

Change the order's status back, as you would for any order. The order note stays, so there is a record of what happened.

## Typed items as draft products

When you have sold the same typed item a few times, you can turn it into a product.

1. Open an order that has an item you typed in.
2. In the **Order actions** box, choose **Save typed items as draft products**.
3. Select **Update**.

A draft product is made for each typed item, with its name and price. An order note lists them. The drafts wait under Products for you to finish and publish. Nothing is published for you, and the order's lines stay as they are.

Screenshot: `gallery-6.png`. Alt text: The Products list showing two typed items saved as draft products.

The action is offered once per item. If you delete the order, the drafts stay under Products until you publish or delete them.

## Good to know

- The discount, like every change to an order's lines, works on orders WooCommerce treats as editable: Pending payment, On hold, and new orders.
- Repeat order copies lines, delivery charges, and terms. It does not copy fees, discounts, coupons, downloads, or subscriptions.
- The add-on has not been tested with WooCommerce Subscriptions, WooCommerce Bookings, Product Bundles, or tax services.

## Troubleshooting

### "Use saved terms" is not under the Order terms box

It appears once at least one set of terms is saved under WooCommerce > Settings > General > Saved terms.

### "Add discount" is not in the items box

The order is not one WooCommerce lets you edit. Check the status: it needs to be Pending payment or On hold.

### A message says the discount cannot be more than the items on the order

The discount is larger than the order's items before discounts. Type a smaller amount.

### A message says to type a discount above zero

Type a number, such as 10, or a percentage, such as 10%.

### "Mark as paid" is not in the Order actions box

The order is already paid, its total is zero, or it is not waiting for payment.

### A message says that way of paying is not switched on in this shop

The payment method was switched off after the page was opened. Reload the order and choose another one.

### A repeated order is missing a line

A product that can no longer be bought is left out. The new order's note names it. Add a replacement by hand.

### "Save typed items as draft products" is not in the Order actions box

The order has no typed items left to save, or your user account cannot add products. Ask the shop's owner if you need that permission.

## Deactivating and uninstalling

When you deactivate the add-on, its actions and the Add discount button go away. Discounts already on orders stay as fee lines, and orders keep their terms.

Deleting the add-on removes its one setting, the saved terms. It does not change any order or product.

## Getting help

Contact support from your WooCommerce.com account. It helps to say which feature you were using, the order's status, and the exact message you saw.

---

Draft ends.

## Checked against

| Part | Backed by |
| --- | --- |
| Saved terms setting and picker | `includes/class-ioefwp-saved-terms.php` ("Saved terms", "Name, for example Wedding orders", "The terms", "Add another", "Remove", "Use saved terms") |
| Add discount form, its limits and messages | `includes/class-ioefwp-discount.php` and `assets/js/pro-order.js` ("Add discount", "10 or 10%", "Name of the discount", "Take it off", "The discount cannot be more than the items on the order.", "A discount cannot be more than 100%.") |
| Repeat order: what is copied and what is not | `includes/class-ioefwp-order-actions.php`, `repeat_order()`; `readme.txt` |
| Mark as paid: when it is offered, the payment methods, the note | `class-ioefwp-order-actions.php`, `payment_ways()`, `can_mark_paid()`, and `mark_paid()` |
| Draft products: permission, one draft per item, nothing published | `class-ioefwp-order-actions.php`, `save_typed_items_as_drafts()`; `tests/run.php` |
| Uninstall removes one setting | `uninstall.php` |

The screenshots were captured from a test store on 7 October 2026 and each was opened and checked against its alt text. Not checked: the sentence about a payment method being switched off after the page was opened, which is the likely cause of that message, read from the code and not reproduced.
