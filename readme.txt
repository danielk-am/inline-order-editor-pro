=== Alkamy Order Editor Pro for WooCommerce ===
Contributors: danielkam1
Tags: woocommerce, phone orders, manual orders, order discount, repeat order
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create branded PDFs from WooCommerce orders, with editable layouts, document sections, and invoice email attachments.

== Description ==

Alkamy Order Editor Pro adds invoicing and PDF documents to Alkamy Order Editor for WooCommerce. It requires WooCommerce and the Free plugin.

= Keep invoicing on the order screen =

Enable Invoicing mode for stacked billing and shipping details, customer references, custom fields, and document sections. Add section headings and text, then drag sections into order. Free's inline item editing remains available.

= Design documents with blocks =

Configure the logo, company details, A4 or Letter paper, compact line spacing, and footer under WooCommerce > Invoicing. Use WooCommerce > PDF layouts to arrange dynamic document blocks with supported headings, paragraphs, groups, columns, images, lists, and reusable patterns. Assign different layouts to each document type.

= Preview and download =

Each export row provides Preview and Download buttons for invoices, quotations, packing slips, delivery orders, and credit notes for existing refunds. Packing slips and delivery orders keep quantities and omit monetary values. Local rendering needs no external payment-service connection. Two-decimal currencies are required.

Saving the order or its sections fills missing assigned document numbers. Invoice, quotation, delivery-order, and packing-slip sequences are separate from the WooCommerce order reference. Previews consume no number.

= Attach the issued invoice =

Enable automatic PDF attachments for selected WooCommerce customer emails. The issued number and PDF are archived privately in the database. Email resends and authorised customer downloads reuse that document, even after later layout edits. Issued and reserved invoice numbers cannot be changed.

= Existing Pro order tools =

* Named terms presets, with an editable copy on each order.
* Fixed or percentage order discounts without a coupon.
* Repeat an order as a new Pending payment order at current catalogue prices.
* Record cash or enabled offline payments through WooCommerce.
* Save typed items as draft products, ready for review and publication.

= Current status and limits =

Pro is free and open source under GPLv2 or later. Pro means additional features, not a paid tier. No purchase, subscription or license key is required. Updates are manual ZIP uploads; no automatic update channel is configured. This is not an approved or published WooCommerce.com product. The invoicing workspace retains its documented alpha limitations. Legacy saved license options are left untouched and are not read or sent to a licensing server.

Test on a backup copy first. Line editing follows Free's editable-order protections. Percentage discounts are calculated once. Repeat order does not copy payment details, coupons, fees, discounts, downloads, or subscriptions. Compatibility with subscriptions, bookings, bundles, and external tax services needs separate testing.

Save order changes before relying on a final PDF. An export does not charge a customer, record payment, create a refund, or send an email. Credit notes represent existing WooCommerce refunds. InvoiceNow/Peppol transmission is not implemented. Back up the database containing the invoice archive and number registry, as well as site files.

== Installation ==

1. Install and activate WooCommerce and Alkamy Order Editor for WooCommerce. Use the current Free 1.0.10-alpha package with Pro 1.3.6.
2. Upload the Pro ZIP under Plugins > Add Plugin > Upload Plugin, then activate it.
3. Set your logo and business details under WooCommerce > Invoicing > Document branding and PDF layouts.
4. Open an order and enable Invoicing mode. Edit the sections and save the order.
5. Use Preview and Download in Document exports. To enable automatic attachments, configure Invoice emails and customer downloads under WooCommerce > Invoicing.

== Frequently Asked Questions ==

= Does Pro work on its own? =

No. Install and activate WooCommerce and Alkamy Order Editor for WooCommerce first.

= Can I create different PDF layouts? =

Yes. Create and publish supported block layouts under WooCommerce > PDF layouts. Assign them by document type in Invoicing, or choose an alternative on an order. Preview with a representative long order before use.

= Does a layout change update old invoices? =

An issued invoice retains its archived PDF and number. Resends and authorised customer downloads use the archive, rather than regenerating it from the new design.

= Does this process payments? =

Use your existing WooCommerce gateways. Eligible unpaid invoices link to the WooCommerce customer payment page. Document custom fields can optionally be passed through the official WooPayments and Stripe metadata hooks when payment is processed. Exporting a PDF does not take payment.

= Does it send InvoiceNow invoices? =

No. Customer UEN and Peppol ID fields can be recorded, but InvoiceNow/Peppol transmission is not implemented.

= What happens if an attachment cannot be generated? =

WooCommerce's normal email can still send without a PDF. Inspect the order's delivery error and the alkamy-invoice-delivery WooCommerce log before resending.

== External services ==

Local PDFs render inside WordPress. Supported local PNG/JPEG images are embedded from the Media Library. External images, scripts, PHP, and arbitrary HTML are excluded from templates. Local PDF exports do not send order data to an external document service.

Custom fields have a Send to payment provider checkbox, off by default. Selected values are added through official WooPayments and Stripe metadata hooks when those gateways process a payment. Existing metadata is preserved. Values longer than 500 characters and additions beyond 50 total entries are skipped. Editing a paid order does not update its remote payment record.

In the standard distribution, optional WooPayments and HitPay setup links are disclosed referrals and open the provider/referral destination. The WooCommerce.com submission variant omits these links. They do not automatically establish an agency relationship. Existing WooCommerce gateways handle payments.

The optional legacy hosted document service remains separate and disabled until configured by an administrator. That path sends order/customer details, custom fields, and section text to its service and communicates with Stripe. Its published service policies and hosting, support, and retention arrangements are required before that service is offered; source policy drafts are not effective public policies.

Stripe privacy: https://stripe.com/privacy
Stripe terms: https://stripe.com/legal

== Screenshots ==

1. Invoicing workspace on a demonstration order.
2. Edit and reorder document sections.
3. Arrange a PDF layout in the WordPress block editor.
4. Preview the rendered PDF.
5. Preview and download buttons for each document.

== Changelog ==

= 1.3.6 =
* Make Pro free and open source: Pro identifies additional features, not a paid tier.
* Remove commercial license activation and update hooks; keep existing features and saved options intact.
* Document manual ZIP updates and the Free 1.0.10-alpha dependency.

= 1.3.5 =
* Add direct-purchase license activation and updates. Installed features remain available after expiry.

= 1.3.3-alpha =
* Rename the product with Alkamy branding; existing plugin and data identifiers remain compatible.

= 1.3.2-alpha =
* Align document-number fields and preview controls into matching rows.

= 1.3.0-alpha =
* Automatic archived invoice attachments, private customer downloads, monthly numbering and WPC historical-document import.

= 1.2.3-alpha =
* Consistent editable fulfilment fields and customer actions beneath the customer dropdown.

= 1.2.2-alpha =
* Assigned document numbers with separate WooCommerce references and duplicate checks.

= 1.2.1-alpha =
* Visible logo/company presets, compact spacing and selectable PDF footer types.

= 1.2.0-alpha =
* Local PDF preview/download, including paid orders, signed discounts and existing refund credit notes.
* Folio block templates, reusable patterns, shared branding and per-order layout selection.
* Narrower sidebar and preserved inline editing.

= 1.1.0-alpha =
* Local alpha bundles the Alkamy document workspace in Pro, preserving order/service keys and avoiding standalone module collisions.
* Existing order workflows retained. PDF service, paid-order invoice support, and negative discount mapping remain release blockers.

= 1.0.3 =
* The Add discount form now opens on its own line under the order's totals, so the order's buttons stay on one line.

= 1.0.2 =
* Text is now trimmed the same way on every PHP version, ahead of a change to PHP's defaults in 8.6.
* A line is now checked by its kind before it is copied to a repeat order or counted for a discount.

= 1.0.1 =
* The message after adding a discount now says what the order total is, and what it was.

= 1.0.0 =
* First release.
