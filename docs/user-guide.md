# Create an order document

Install and activate WooCommerce, Alkamy Order Editor for WooCommerce, and Alkamy Order Editor Pro for WooCommerce. Try the alpha packages on a backup copy of your store first.

## Set the shared design

Open **WooCommerce > Invoicing > Document branding and PDF layouts**.

Set the legal business name, address, contact details, and any applicable registration identifiers. Choose a local PNG or JPEG logo from the Media Library, up to 256 KB and 2000 × 2000 pixels. Set the logo width, A4 or US Letter paper, standard or compact line spacing, and prepared-by text.

Choose a footer: signature fields and text, text only, company details and text, or none. Save the document design before checking its preview.

The shared settings supply the default design. Each document type can use the Folio house layout or a published custom layout.

## Make a custom layout

1. Open **WooCommerce > PDF layouts** and add a layout.
2. Start with a PDF document pattern or arrange the document yourself.
3. Use the dynamic document blocks for logo, business details, document identity, recipient, references, items, totals, sections, and footer. Add supported headings, paragraphs, lists, groups, columns, and local PNG/JPEG images around them.
4. Publish the layout, then assign it to a document type under **WooCommerce > Invoicing**. An order's layout selector can choose an alternative.
5. Preview a short order and a long order. Check page breaks, columns, item descriptions, sections, and the footer before relying on the design.

The editor uses a supported subset of WordPress blocks. Arbitrary HTML, PHP, scripts, shortcodes, remote images, and unsupported blocks do not become executable PDF content. Large column content can stack vertically for pagination. Layout revisions preserve previous designs.

## Prepare an order

Open the order under **WooCommerce > Orders** and enable **Invoicing mode**. Edit customer details, then add any custom label/value fields. Record customer UEN or Peppol ID when needed. These identifiers do not send an InvoiceNow invoice.

Add a heading and content to each document section. Use supported Markdown for formatting, and drag the section handle to reorder sections after totals and payment. Save the sections or update the order. Named order terms can be managed separately under General settings.

Missing assigned invoice, quotation, delivery-order, and packing-slip numbers are filled when saved. Existing references remain. The WooCommerce order number stays a separate reference. Invoice numbering follows the configured monthly sequence; the other document types have their own monthly series. Opening the screen or a preview does not consume a number.

## Preview and download

In **Document exports**, each document has separate **Preview** and **Download** controls. Check the rendered PDF before sharing it.

- **Invoice:** items, values, totals, and the assigned invoice number.
- **Quotation:** the order's saved details as a quotation.
- **Packing slip and delivery order:** items and quantities, with monetary values omitted.
- **Credit note:** details of an existing WooCommerce refund. Create and check the refund through your normal WooCommerce workflow first.

Save order changes before the final export. Exporting does not send an email, take payment, mark the order paid, or create a refund. Two-decimal currencies are required.

## Enable invoice email attachments

Open **WooCommerce > Invoicing > Invoice emails and customer downloads**. Enable automatic attachments, select the customer emails, and save. Processing and completed emails are the default selections; order details/customer invoice emails are optional.

Configure the invoice prefix and digit count before issuing documents. The prefix must include `{year}` and `{month}`. New numbering follows the store's configured time, rather than a separate document timezone.

An issued invoice is archived with its number. Resends and authorised customer downloads reuse the exact archived bytes. Later order, branding, and layout changes do not rewrite it. Issued or reserved invoice numbers cannot be cleared or changed.

Back up the database containing the archive and number registry. If generation fails, the normal WooCommerce email can still send without an attachment. Inspect the order error and WooCommerce logs with source `alkamy-invoice-delivery` before resending. [Invoice delivery operations](invoice-delivery-1.3.md) describes recovery and legacy imports. Do not delete number claims or locks to reuse a number.

Alkamy skips automatic attachments while WP Clever PDF Invoices is active, to avoid duplicate invoices. Migrate and verify historical documents before retiring an older document plugin.

## Use the existing Pro order actions

**Saved terms:** keep named terms under **WooCommerce > Settings > General > Saved terms**, then load a copy onto the order. Edit the copy and update the order.

**Add discount:** enter an amount or percentage in the items panel. It becomes a negative fee line. A percentage is calculated once; remove and re-add the discount if the items change.

**Repeat this order as a new order:** choose the action and update. The new order is Pending payment, with catalogue items at today's prices and one-off items copied as entered. Payment details, coupons, fees, discounts, downloads, and subscriptions are not copied. Unavailable products are omitted and named in the order note.

**Mark as paid:** use cash or an enabled offline payment method only after receiving the payment. WooCommerce's payment completion, stock, and email behaviour applies. The action does not collect money.

**Save typed items as draft products:** create draft products for review under **Products**. They are not automatically published.

## Pass selected references to a payment provider

Check **Send to payment provider** only on custom fields that belong in the payment record. The default is off. When an official WooPayments or Stripe gateway processes payment, the plugin adds those values through the gateway's metadata hooks.

Existing metadata is preserved. Values over 500 characters and additions beyond 50 total entries are skipped. Changing the order after payment does not update its remote payment record. Do not put sensitive personal or payment information in these fields.
