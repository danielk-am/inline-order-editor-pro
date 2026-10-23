# PDF layouts 1.2.0-alpha verification

15 October 2026. This record supersedes the 1.1.0 candidate limitations in invoicing-integration-verification.md for the local PDF path. Deployment receipt is stored in the task workspace work/pdf-deployment.

## Merchant controls

WooCommerce → Invoicing owns logo, business/contact details, footer, A4/Letter defaults and default layout per document type. WooCommerce → PDF layouts opens a private native Gutenberg editor with revisions, safe core primitives, dynamic document blocks, built-in patterns and synced reusable patterns. Publish a layout to make it selectable on orders. The order Document sections workspace selects document type, layout and paper, edits sections and previews unsaved document fields. Save item edits before exporting PDFs. PDFs are current order exports, not a statutory immutable invoice ledger.

Supported local exports: invoice, quotation, packing slip, delivery order and credit note for an existing refund. Currency currently requires two decimal places. Payment links use native WooCommerce eligibility. Packing slips and delivery orders omit monetary values. Existing service-issued records preserve their original snapshots.

## Acceptance evidence

80 real WooCommerce checks passed in HPOS and legacy modes. Local record generation preserves the persisted order hash and made zero outbound requests. Partial refunds export their actual credit amount. Layout contracts (20), renderer/snapshot assertions (15), editor controls (9), loader/service-gate/output boundaries and package checks passed. Anonymous layout REST access denied.

Native isolated Chrome order-screen checks passed PDF byte rendering, 320px sidebar, eight-line note height, address synchronization, billing/note/custom field/provider checkbox/section persistence, and no overflow at 1081, 768 and 390px. No console errors. Five sample PDFs inspected; 100 stress-test items retained across eight pages.

IAB's blob-backed editor iframe is blank for both this CPT and stock WordPress pages. Editor acceptance uses isolated native Chrome; no core workaround was applied. This limitation must not be represented as an IAB acceptance pass.

Final package Plugin Check and production readback are recorded in the deployment receipt. The directory dependency warning for the privately installed free plugin is not a directory readiness claim. Live payments/provider metadata transport and public directory submission were outside this release acceptance.
