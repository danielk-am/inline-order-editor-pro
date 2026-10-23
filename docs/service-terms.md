# Alkamy invoicing service terms

**Draft, unpublished. Prepared 14 October 2026 for owner and legal review. No effective date, acceptance flow, or production service commitment has been assigned.** This draft records current functionality and proposed service boundaries. Unresolved commercial and legal terms are identified below.

## Parties and service

The proposed provider is Alkamy Pte. Ltd. (formerly 1dr0 Pte. Ltd.), UEN 201429706H, based on the owner’s recorded legal-name decision. Confirm the current registry record and contracting operator before publication.

The service receives document snapshots from an authorized WooCommerce store, renders PDFs, preserves issued snapshots, and supports the current Stripe-backed invoice/credit workflow. The plugin's local order editing operates separately. No production endpoint or general-availability service is currently offered by this alpha.

A merchant connecting a production service must have authority to operate the store, transmit its order data, and authorize the selected provider actions. Connection credentials identify the store. They must be protected from unauthorized use.

## Merchant data and documents

The merchant provides the seller/customer identity, order lines, currency, taxes, custom fields, terms, and shipping details. The merchant remains responsible for reviewing those details, deciding which information belongs on each document, and providing customers with appropriate notices.

The service calculates and renders from the submitted snapshot. It does not establish the merchant's tax registration, verify the customer's UEN/Peppol address, or provide tax/legal advice. Recording identifiers does not certify InvoiceNow or legal compliance.

Issued snapshots are retained independently of later edits to the WooCommerce order. Corrections must follow a documented replacement or credit process. The current alpha does not offer every correction workflow, so merchants must reconcile uncertain cases before retrying.

## Current functional limits

- Actual PDF previews and issuance require a separately configured service. A4/Letter in the local Layout guide does not set the PDF page size; the current renderer generates A4 PDFs.
- Invoice issuance uses Stripe Connect and requires an eligible unpaid, positive WooCommerce order without existing refunds. It is not yet a general document-only invoice path for paid checkout orders.
- Quotations have no acceptance/signature workflow. Packing slips and delivery orders omit monetary columns and do not change fulfillment status.
- Credit handling is limited to full credits against an issued Stripe invoice. Crediting a paid invoice requests a full provider refund; it is not merely a PDF-generation action.
- Partial payments/refunds, already externally credited invoices, conflicting WooCommerce refunds, or changed totals require manual reconciliation.
- Supported document precision is two decimal places. Currency, line-count, tax/rounding, negative-line, and text/font constraints apply. Long and multilingual production PDFs need validation before use.
- No InvoiceNow/Peppol transmission or IRAS submission is implemented. WooCommerce sales documents alone cannot establish all accounting obligations.
- The plugin does not provide a WooCommerce checkout gateway. Provider recommendation links do not route invoice payments through WooPayments or HitPay.
- Automatic PDF attachments and arbitrary custom-section integration with native WooCommerce emails are not implemented. Dormant alpha email operations are not a supported transactional email product.

These limits must be updated against the release that customers actually receive. A planned feature is not an available service.

## Payments, refunds, and third-party providers

Stripe handles payment authorization, customer payment details, onboarding, and its own account eligibility. Connected-account availability and supported payment methods depend on Stripe configuration and approval. The merchant’s agreement with Stripe remains separate from these service terms.

The existing invoice flow uses direct connected-account invoices and can add an application fee. The alpha defaults to zero. No Alkamy service price, paid plan, commission rate, or charge has been agreed by this draft. Any live fee must be disclosed and accepted before it applies, including separate Stripe processing/Invoicing/Connect charges where relevant.

A successful local test or mocked response does not prove connected-account production readiness. Merchants must reconcile provider state with WooCommerce when payment/refund status is uncertain. Retrying with a new operation identifier can duplicate a financial action and is not a supported recovery procedure.

Provider referrals are optional. Eligibility and referral commissions follow the provider's agreement. A signup link does not establish a merchant's agency connection, account approval, or Alkamy invoice-payment integration. Confirm permitted referral placement and destination before distributing links.

## Security and acceptable use

Merchants must protect their WordPress administrator accounts, server configuration, and connection credentials. Keep platform payment secrets in the service runtime. Do not place passwords, API keys, full payment-card details, or unnecessary sensitive data in document text or custom fields.

Use the service only for authorized stores and lawful documents. Do not send fabricated identities, malicious content, or data you have no right to process. These proposed restrictions require review alongside the final suspension/notice process.

The current service is designed for one Node process and one SQLite volume. Production backup, restoration, clock synchronization, infrastructure access, and secure hosting need an agreed operating plan. The alpha has no uptime guarantee, support-response commitment, or tested disaster-recovery promise.

## Privacy, records, and ending service

Read the accompanying [draft service privacy notice](service-privacy.md) for the current data inventory and storage behavior. The final service contract must identify the parties' data roles, approved suppliers, transfers, retention, rights-request handling, and incident process.

No tenant/document termination export or automatic remote deletion route is implemented. Removing the plugin does not remove remote snapshots or provider records. A production termination process must address access revocation, merchant export, legally required retention, and eventual deletion, including backups.

## Support and changes

The service-specific support/privacy contact, service hours, escalation process, and supported version policy remain unset. The owner’s `hey@danielk.am` account identity is not automatically a designated support inbox.

Before launch, define how material service or fee changes are communicated, how merchants accept the terms, and what happens if a merchant declines a change. The draft does not assume acceptance merely from installing an unrelated free plugin.

## Commercial and legal bindings still required

The following clauses require owner decisions and legal review before publication:

| Binding | Decision needed |
| --- | --- |
| Operator and contact | Current contracting entity/address, service support/privacy contact, and verified public details. |
| Access and consent | Production setup, authorized customer classes, acceptance record, data-processing agreement, and service URL. |
| Pricing | Free/paid service boundaries, fees/taxes/billing, renewal/cancellation, and refunds of service charges. |
| Responsibility | Provider/merchant financial responsibility, disputes, losses, indemnity if appropriate, liability limits, and non-excludable rights. |
| Service commitments | Supported versions, maintenance, availability, support/escalation, incident notification, and business continuity. |
| Termination | Suspension reasons/notice, account revocation, exports, retention, deletion, backups, and lawful exceptions. |
| Legal process | Governing law, dispute forum/process, notices, assignment, and enforceable consumer/business distinctions. |
| Release and changes | Approved effective date, public terms/privacy URLs, change-notification and acceptance flow. |

No arbitrary liability cap, retention period, support SLA, jurisdiction clause, or production-hosting claim is supplied here. Those terms must reflect the real service and legal advice.

## Internal evidence, remove before publication

Identity: canonical Drive decision `Alkamy/00 Field Guide/Decisions/2026-09-24-legal-name-alkamy.md`, dated 24 September 2026. Current source: `service/src/store.mjs`, `app.mjs`, `connect.mjs`, `stripe-flow.mjs`, `server.mjs`, `pdf.mjs`, and plugin document/action handlers inspected 14 October 2026. This is a local draft, not an executed agreement or published service policy.
