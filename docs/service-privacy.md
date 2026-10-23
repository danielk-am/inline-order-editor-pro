# Alkamy invoicing service privacy notice

**Draft, unpublished. Prepared 14 October 2026 for owner and legal review. No effective date has been assigned.** This describes the current alpha source. It is not a published privacy policy, a production hosting statement, or permission to process real customer data.

## Operator and scope

The proposed service operator is Alkamy Pte. Ltd. (formerly 1dr0 Pte. Ltd.), UEN 201429706H. This name follows Daniel’s recorded decision of 24 September 2026. A current registry record and the service-specific contractual operator must be confirmed before publication.

This notice covers the separately hosted Alkamy invoicing service used by the WooCommerce plugin. The plugin's local order editor and the merchant's website remain under the merchant's operation. A self-hosted installation has its own operator and needs its own privacy disclosures.

The proposed arrangement is that the merchant directs processing of their customers’ order data, and Alkamy processes that data to provide the selected service. The parties’ legal roles and data-processing terms remain to be agreed. Stripe and payment providers have their own roles and policies.

## Data processed by each action

| Action | Data and purpose |
| --- | --- |
| Edit fields or open the Layout guide | Order details, terms, custom fields, and user preferences are handled in WordPress. The Layout guide is rendered there and does not itself call the Alkamy service. |
| Connect a Stripe account | The service processes the store administrator’s return URL, random connection state, verification challenge, authorization code, and connected-account identifier. It creates store connection credentials so WordPress can authenticate subsequent requests. Stripe handles account authorization and onboarding. |
| Continue the legacy account-onboarding flow | The service sends the merchant email, tenant identifier, country/capability configuration, and return URL to Stripe. Stripe collects onboarding information through its own pages. |
| Request a PDF preview | WordPress sends a draft snapshot containing seller identity, customer and shipping names/addresses, customer email, UEN/Peppol identifiers, order reference, currency, quantities, amounts/tax, custom fields, and section text. The service renders the draft PDF in memory and returns it. The preview route does not save a document snapshot or create a Stripe invoice. |
| Create a document | The service receives the snapshot and an operation key, then stores the snapshot, content hash, document identifier, creation time, number, and issuance state. For invoices, it also creates Stripe customer/invoice records. |
| Download an issued PDF | The service authenticates the store, retrieves its saved snapshot, and generates a PDF in memory for return to WordPress. PDF binary files are not written by the current renderer. |
| Refresh payment/refund status | The service retrieves Stripe invoice/refund status and stores amounts, status, payment-page URL, and provider identifiers. Signed Stripe invoice webhooks can trigger the same reconciliation. WooCommerce can schedule later refreshes. |
| Create a full credit note | The service copies the original snapshot, records its relationship to the invoice, creates a Stripe credit note and, for a paid invoice, requests a full refund. It retains credit/refund identifiers and status. |
| Select a provider referral link | The browser opens the provider or referral destination. The current panel does not send the order snapshot as a referral payload. The destination’s own tracking and privacy terms apply. |

Custom fields and document sections are merchant-entered content. They can contain personal or confidential information. Merchants should enter only information needed for the document, and should keep passwords, API keys, full payment-card details, and unnecessary sensitive information out of these fields.

## Stripe and payment information

The invoice flow sends Stripe the customer’s name/email, address as a description, order reference, item descriptions, quantities, amounts/tax, currency, payment deadline, and document references. Stripe returns identifiers, document/payment links, and status information. The service stores those references alongside the snapshot.

Each custom field has a Send to payment provider checkbox, off by default. Only fields explicitly selected by the merchant are added to payment metadata through the official WooPayments/Stripe gateway filters, or to a service-issued Stripe invoice. Unselected fields are not added to payment metadata. Existing provider metadata is retained; values longer than 500 characters and additions beyond the provider’s 50-entry limit are omitted. Metadata forwarding occurs when the provider creates or updates the relevant payment record; editing an already-paid order does not promise an update to its existing remote record.

The checkbox controls payment metadata only. The complete document snapshot, including unselected custom fields, is still sent to Alkamy when the merchant requests a service-backed PDF preview or creates a document. Metadata is not automatically displayed on payment-provider invoices or receipts.

Customers enter payment details on Stripe’s hosted payment page. The current service does not request full card numbers or CVC values. Its webhook endpoint receives a signed Stripe event body and processes matching invoice events. Provider retention and processing follow [Stripe’s privacy policy](https://stripe.com/privacy) and the merchant’s provider agreement.

## Storage and access

The service uses a SQLite database and its write-ahead-log files. It stores tenants, store secrets, store return URLs, connected-account identifiers, document snapshots, operation keys/hashes, provider identifiers/status, replay-prevention nonces, activation-code hashes, connection state, and processed webhook-event identifiers.

Store secrets are stored as text in the service database. WordPress also stores its connection secret in a non-autoload option, unless server configuration overrides it. Platform Stripe credentials are injected into the service runtime. The source does not implement application-level database encryption at rest.

Signed store requests include a timestamp, nonce, and signature. Service requests are scoped to the authenticated tenant. The server creates its data directory with restrictive permissions and sets the database file to owner-only access. Production TLS termination, administrator access, key rotation, backups, and infrastructure protections remain deployment responsibilities.

The application logs startup mode and limited provider-error diagnostics: error type, code, and provider request identifier. It does not deliberately log document bodies in the inspected error handler. Reverse proxies, hosting systems, backups, and support tools may process additional technical data depending on the eventual deployment.

## Retention and deletion

**No automatic retention/deletion schedule exists for tenant records, issued snapshots, or processed webhook-event identifiers in the current alpha.** They remain until the operator removes them or the database. Uninstalling the WordPress plugin does not delete the remote service database or Stripe records.

The code applies these temporary limits:

- Replay-prevention nonces older than 10 minutes are removed when a later nonce is processed.
- Connection stages expire after 15 minutes. Expired rows are cleaned when a new connection stage is saved. Expiry does not guarantee immediate physical deletion.
- Legacy activation codes expire after their configured lifetime, 15 minutes by default. Expired codes are cleaned when another activation is created. Successful redemption consumes the code.
- Draft PDF snapshots are processed in memory by the preview route. Persisted issued snapshots are separate records.

Backup and log retention, document retention, termination export, deletion turnaround, and legal-hold exceptions have not been configured. Before launch, adopt and implement a schedule appropriate to the merchant’s accounting obligations and the service contract. Confirm deletion across backups and provider records rather than promising an unsupported purge.

## Hosting, transfers, and suppliers

No production service endpoint, hosting provider, processing country, backup location, or final supplier list has been selected for this alpha. The website’s existing hosting arrangements do not establish the invoicing service’s location. Stripe is the implemented payment supplier. WooPayments/HitPay signup links lead to separate services.

The inspected service does not send document snapshots to an AI provider, analytics service, or InvoiceNow Access Point. InvoiceNow transmission is not implemented. Any future supplier or international-transfer arrangement must be reviewed and disclosed before use.

## Access, correction, and enquiries

Customers should first ask the merchant about their order and documents. The service operator must establish a verified process for merchant access, corrections, deletion requests, account termination, and privacy complaints before production use. Issued financial-document corrections may need a new document or credit note rather than alteration of the original snapshot.

**Service privacy contact: not yet designated.** The owner supplied `hey@danielk.am` as the HitPay account identity. A separate Alkamy website privacy draft lists `hey@alkamy.ltd`; neither source alone designates the production invoicing service’s support or privacy inbox.

## Publication requirements

Complete these bindings before treating this as a live notice:

1. Confirm legal operator, current registry evidence, privacy contact, merchant/customer data roles, and data-processing agreement.
2. Select service URL, hosting/backup countries, suppliers, technical logging, access controls, incident response, and international-transfer terms.
3. Implement and document retention, export, deletion, termination, and backup handling.
4. Provide clear consent before connection and externally rendered PDF previews. Include any enabled payment-metadata forwarding.
5. Approve the final notice, public URL, effective date, and change-notification process. Link that live notice from the plugin; a repository draft does not satisfy a published service-policy binding.

## Internal evidence, remove before publication

Identity source: canonical Drive decision `Alkamy/00 Field Guide/Decisions/2026-09-24-legal-name-alkamy.md`. Older legal cabinet INDEX retains the old name. Contact comparison: website draft `Alkamy/07 Delivery/Website/privacy-v2.json` and the owner’s HitPay account instruction in this chat.

Technical source: `service/src/store.mjs`, `connect.mjs`, `app.mjs`, `server.mjs`, `stripe-flow.mjs`, `pdf.mjs`, and the plugin’s service/documents/admin handlers, inspected 14 October 2026. Recheck this notice after runtime changes; source observations do not prove a deployed control.
