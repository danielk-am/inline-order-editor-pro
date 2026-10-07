# Woo Marketplace docs: full notes, and where both plugins stand

Working record for getting Inline Order Editor for WooCommerce (free) and Inline Order Editor Pro onto the WooCommerce.com Marketplace. Not shipped in either ZIP. It sits beside `woocommerce-marketplace-notes.md`, which holds the earlier submission notes and the draft testing instructions.

## Record

- **Work ID:** IOE-MKT-1
- **Outcome (derived from the request):** every page under developer.woocommerce.com/docs/woo-marketplace is read and noted, each thing it asks for is checked against both plugins, the gaps that can be closed in the repositories are closed and verified, and the rest is listed with an owner.
- **Acceptance owner:** Daniel.
- **Scope and authority:** reading, local edits, local builds and local test stores. Nothing was committed, pushed or released until Daniel said "Go" on 7 October 2026. That covered committing and pushing both repositories and releasing free 1.0.3, and nothing else. No account, form or message was touched.
- **Acceptance criteria (derived, to confirm):**
  - AC1, must: all 12 pages in the section are read, with their last-changed dates.
  - AC2, must: each requirement is marked for both plugins, with the evidence or the reason it could not be checked.
  - AC3, must: package-level gaps are fixed, with a test that fails without the fix.
  - AC4, should: everything left has an owner and a next step.
- **State on 7 October 2026:** cycle 1 is done. Free 1.0.3 is released and both repositories are pushed. Formal UAT was not run: see UAT below. The acceptance criteria above are still derived ones that Daniel has not confirmed one by one. Cycle 2 drafted the listing text and the documentation in English (UK): see "Cycle 2" below.

## Where things stand

The Marketplace docs turned up two things that were wrong in the packages. Both are fixed, and the first is released as free 1.0.3:

1. **The free plugin had no `changelog.txt`.** The Marketplace rejects an upload without one. Version 1.0.3 adds it, in the Marketplace's format, and a new test checks it on every build.
2. **The Marketplace icon broke two icon rules.** The earlier Pro icon had rounded corners baked in and artwork out to the edge. The new 160 x 160 icon has square corners and keeps the artwork inside the middle 112 x 112. The free repository now has a copy too.

What still needs you before a submission:

- Apply for the partner account, accept the vendor agreement, and enter the business details and support email.
- Decide the name, the price, and what to do about the donate link.

Drafted in cycle 2, with the current name: the Marketplace product page text, the FAQs and the documentation for both. Cycle 3 captured seven gallery images for Pro.

What cannot be checked from here: the QIT API and end-to-end suites, the Marketplace's own changelog validator, the ZIP name it will ask for at upload, and behaviour beside the six popular paid extensions it names.

## Sources read on 7 October 2026

Read from the docs' source in the `woocommerce/woocommerce` repository, `docs/woo-marketplace/`, at trunk commit `6173f77bb`. The dates are when each file last changed.

| Page | Last changed | Applies to us |
| --- | --- | --- |
| Woo Marketplace (index) | 22 Feb 2026 | Index only |
| Getting started | 22 Jun 2026 | Yes |
| Monetization expectations | 2 Mar 2026 | Yes: freemium |
| Submitting your product | 7 Jul 2026 | Yes |
| Managing your account | 11 Mar 2026 | Yes, after approval |
| Managing products | 11 Mar 2026 | Yes, after approval |
| Product update guidelines | 22 Feb 2026 | Yes |
| Excellence Verified badge | 21 Sep 2026 | Later, optional |
| Product page content and assets | 11 Aug 2026 | Yes |
| Content style guide | 11 Aug 2026 | Yes |
| Support guidelines | 11 Aug 2026 | Yes, after launch |
| Billing API for SaaS products | 30 Sep 2026 | No: neither plugin is a SaaS product. Headings skimmed only. |

Three pages the section points to were read as well, because it leans on them: "Formatting for Changelog.txt", "Support and documentation for WooCommerce extensions", and the headings of the grammar, punctuation and capitalization guide. The UX guidelines, the extension development best practices and the vendor agreement were not read in this pass.

## What each page asks for

**Getting started.** Products should solve a real merchant problem, be secure, documented and maintained, come with good support, and have a clear way to earn. The steps are: prepare the product, apply for a partner account, submit after approval, go through review, then launch and keep it updated. The vendor's share is 70% of sales.

**Monetization expectations.** A free plugin with no paid path is not accepted. A standalone plugin is sold with a single-site licence, at a price close to the single-site price anywhere else. A free plus paid pair is listed as freemium, which suits a paid add-on plugin on top of a free one. Integration plugins and SaaS products have their own billing rules, which do not apply here.

**Submitting your product.** The account has to be approved first. The checklist covers: revenue share and no spam, affiliate or outside upsell links; a trademark-safe, distinctive name; a price equal to or lower than elsewhere; security best practices; PHP 7.4 as the minimum; support for the two latest major releases of WooCommerce and WordPress; passing seven QIT tests (API, end-to-end, activation, security, PHP compatibility, malware, validation); tested critical flows, the UX guidelines and the WordPress accessibility coding standards; one core purpose that works without much setup; no breakage beside Bookings, Subscriptions, Product Add-Ons, Product Bundles, Min/Max Quantities and Shipment Tracking; Cart and Checkout block compatibility with no reliance on shortcodes; and HPOS compatibility, which is mandatory. A free plus paid pair is two submissions, later joined on one product page. The form asks for the ZIP, business details and testing instructions. Failed tests show on the submission, and replacing the ZIP reruns them. A decision comes within 30 days of the business review starting.

**Managing your account.** The store settings need a business legal name, a contact address, a vendor email, a support email and a website. Changing the business name means signing the partner agreement again. The vendor logo must not use Woo marks. The profile may link to the vendor's homepage, and to no other products or contact forms. The dashboard has a changelog validator, a customer subscription lookup and a vendor lookup. Extra vendor admins need a WooCommerce.com account with two-step authentication.

**Managing products.** Product pages, versions and documentation are edited in the vendor dashboard. Reports show earnings, commissions on net sales, and subscriptions.

**Product update guidelines.** Update at least every six months and keep pace with WooCommerce releases, or the product is flagged for removal. Use semantic versioning and customer-facing changelogs. Each uploaded version is tested by QIT, and activation, security and malware must pass before it goes live. Uploads fail when the ZIP or its top folder has the wrong name, when `changelog.txt` is missing or badly formatted, or when the version in the plugin header does not match the version typed at upload or the changelog. Updates can also be deployed with an application password and a POST request. A custom end-to-end test package is encouraged.

**Excellence Verified badge.** An optional badge with four parts: secure, compatible and maintained, all measured by QIT, and a merchant rating of 4.0 or more with enough reviews. Its page names validation and a working test package as the usual blockers, and asks for an honest HPOS declaration and current "tested up to" headers.

**Product page content and assets.** The product icon is the product's logo at 160 x 160 in JPG or PNG, shown at 80 x 80, with the artwork inside the middle 112 x 112 and no rounded corners. A hex colour sits behind it on highlight cards. The short description is used on cards and in search results. Gallery images are 896 x 550 or larger, starting with the featured image, with one optional video. The description should lead with three to five benefits in plain words, sell the outcome before the features, keep technical detail for the documentation, and use no emojis. FAQs get their own section and should be short and practical. Features, search keywords and compatibility are filled in by AI for the vendor to review. Requirements have their own field. Names are one to five words, easy to scan, distinctive, not a description, and never "best" or similar. Name and price changes go through the Marketplace team. A demo should show the front end and the back end over HTTPS with direct links.

**Content style guide.** Write "Product Name for WooCommerce", never the other way round. Use American English, the Oxford comma, active voice, sentence-case headings and "ecommerce". Use H2 and H3 only, left-aligned, with no custom colours, fonts, borders or spacing. No emojis. No links to outside websites: support and documentation links point at WooCommerce.com. Two tick-list classes and a `[box]` callout are provided. Documentation opens with what the extension does, uses the installation pattern the Marketplace supplies, covers every feature and setting, and adds troubleshooting. Screenshots use the modern admin colour scheme and a recent core theme, and carry alt text.

**Support guidelines.** Support is part of the vendor agreement, and poor support can get a product removed. Vendors answer by email, reply to reviews and feature requests, and keep good support ratings. WooCommerce handles refunds, coupons, escalations and live chat. Tickets arrive at the support email with the product, subscription dates and system status report. Set up an autoresponder that links the privacy policy and carries no promotion. Ask for store access only as a last resort, through a temporary admin account, and never ask for a plain-text password.

**Formatting for Changelog.txt.** A `changelog.txt` is required. It opens with `*** Extension Name Changelog ***`, then one block per version headed `YYYY-MM-DD - version X.Y.Z`, with `* Type - text` lines. The types are add, added, feature, new, developer, dev, tweak, changed, update, delete, remove, fixed and fix. Lines can be nested one level.

## Requirement by requirement

Status words: **Met**, **Fixed** (in this cycle, locally), **Not met**, **Yours** (a decision or action only you can take), **Not checked**, **Later**.

### Account, agreement and support

| ID | Asked for | Free | Pro | Evidence or next step |
| --- | --- | --- | --- | --- |
| A1 | An approved partner account before submitting | Yours | Yours | Apply at woocommerce.com/partners. Not started. |
| A2 | The vendor agreement, at 70% of net sales to the vendor | Yours | Yours | Read and accept it yourself. Not read in this pass. |
| A3 | Business legal name, contact address, vendor email, support email, website | Yours | Yours | Entered under Settings > Store after approval. |
| A4 | A vendor logo with no Woo marks, and a profile that links only to your homepage | Yours | Yours | The danielk.am mark has no Woo marks. |
| A5 | Email support, replies to reviews and feature requests, an autoresponder, safe handling of store access | Yours | Yours | A standing commitment once listed. |

### Business model and name

| ID | Asked for | Free | Pro | Evidence or next step |
| --- | --- | --- | --- | --- |
| B1 | A path to earning for a free plugin | Met | Met | Submit as a freemium pair: two submissions. The free plugin cannot go in alone. |
| B2 | Paid features in a separate add-on plugin | Met | Met | The add-on starts from the free plugin's `ioefw_loaded` action. The free plugin has no locked feature. |
| B3 | A single-site price equal to or lower than elsewhere | Later | Yours | The add-on is not sold anywhere yet. |
| B4 | No spam, affiliate or outside upsell links | Yours | Met | The free `readme.txt` has a donate link to danielk.am/tip and a link to GitHub. The plugin headers link to GitHub and danielk.am. The add-on has only its header links. Decide whether the donate link stays in a Marketplace build. |
| B5 | A name of one to five words, in the "for WooCommerce" form, distinctive, not a description | Yours | Yours | The form is right. "Inline Order Editor" describes the product, which the naming rules advise against. "Order Jotter" is the checked fallback. |

### Technical checklist

| ID | Asked for | Free | Pro | Evidence or next step |
| --- | --- | --- | --- | --- |
| T1 | Security best practices | Met locally | Met locally | PHPCS security rules, Semgrep and gitleaks find nothing. This is a local check, not QIT's. |
| T2 | PHP 7.4 as the minimum | Met | Met | `Requires PHP: 7.4`. Tested on PHP 7.4.33 and 8.5.10. |
| T3 | The two latest major releases of WooCommerce and WordPress | Met by range | Met by range | Current on 7 October 2026: WordPress 7.1.3 and 7.0.7, WooCommerce 11.1.2 with 11.0 before it. Both plugins declare WordPress 6.8 and WooCommerce 10.0 as minimums. Tested on WordPress 7.1.3 with WooCommerce 11.1.2, and on 6.8.11 with 10.0.6. WordPress 7.0 and WooCommerce 11.0 were not run. |
| T4 | Seven QIT tests passed | Not checked | Not checked | QIT runs on the submission. Local stand-ins pass for activation, security, PHP compatibility, malware and validation. Nothing local stands in for the API and end-to-end suites. |
| T5 | HPOS compatibility | Met | Met | Declared, and the test suites pass with HPOS and with post-based storage. |
| T6 | Cart and Checkout blocks, and no reliance on shortcodes | Met | Met | Declared compatible. Neither plugin touches the cart or checkout, and neither has a shortcode. |
| T7 | No breakage beside the six popular extensions | Not checked | Not checked | Bookings, Subscriptions, Product Add-Ons, Product Bundles, Min/Max Quantities and Shipment Tracking are paid. Testing needs copies. |
| T8 | Tested critical flows, one core purpose, works without setup | Met locally | Met locally | 107 and 64 scripted checks. Both work on activation with no settings. The UX guidelines page itself was not read in this pass. |
| T9 | WordPress accessibility coding standards | Partly | Partly | Keyboard paths, focus handling and screen reader messages were built and checked with scripts. No audit against the standard. |

### Package and updates

| ID | Asked for | Free | Pro | Evidence or next step |
| --- | --- | --- | --- | --- |
| P1 | `changelog.txt` in the ZIP | Fixed | Met | Free 1.0.3 adds it. `tests/package.php` fails on the 1.0.2 build and passes on 1.0.3. |
| P2 | The changelog in the Marketplace format | Fixed | Met | Both follow the format page. `tests/package.php` checks the title line, the dated version lines and the entry lines. The Marketplace's own validator is in the vendor dashboard and was not run. |
| P3 | One version everywhere: header, upload form, changelog | Met | Met | `tests/package.php` compares the header, the version constant, the readme's Stable tag and the newest changelog entry. |
| P4 | The ZIP and folder name WooCommerce.com expects | Not checked | Not checked | It shows the expected name at upload. Today the folder is the plugin slug. |
| P5 | An update at least every six months, with semantic versions | Later | Later | Semantic versions are in use. |
| P6 | A custom end-to-end test package | Not met | Not met | Optional for listing, needed for the badge. It needs the QIT command line and Docker. |

### Product page and documentation

| ID | Asked for | Free | Pro | Evidence or next step |
| --- | --- | --- | --- | --- |
| L1 | A 160 x 160 product icon, square corners, artwork inside the middle 112 x 112 | Fixed | Fixed | `design/marketplace-icon-160x160.png` in both repositories, from `design/marketplace-icon.svg`. Measured: all four corners are the background colour, and the artwork sits between pixels 25 and 134 across and 29 and 130 down. |
| L2 | A highlight card colour | Yours | Yours | `#2563EB` matches the icon's background. |
| L3 | A short description | Drafted | Drafted | `design/marketplace-listing.md`. |
| L4 | Gallery images at 896 x 550 or larger | Met by size | Met by size | The free plugin's five screenshots and the add-on's seven gallery images are 1280 x 860. The add-on's are in `design/gallery/`. |
| L5 | A description that leads with three to five benefits, in plain words, with no emojis | Drafted | Drafted | One shared page in `design/marketplace-listing.md`, written with the current name. |
| L6 | American English, the Oxford comma, sentence-case headings, no outside links | Drafted, except spelling | Drafted, except spelling | The drafts follow every rule but one: they use English (UK) spelling, on Daniel's instruction of 7 October 2026. A reviewer may ask for American spelling. Four words would change: catalogue, colour, cheque and licence. |
| L7 | Short, practical FAQs | Drafted | Drafted | Twelve questions in `design/marketplace-listing.md`. |
| L8 | A requirements entry | Yours | Yours | WooCommerce 10.0, WordPress 6.8 and PHP 7.4 or later. The add-on also needs the free plugin. |
| L9 | A demo over HTTPS showing front and back end | Not met | Not met | Nothing exists. The page calls a demo important and does not call it required. |
| L10 | Documentation on WooCommerce.com: introduction, installation pattern, every feature and setting, troubleshooting, screenshots with alt text | Drafted | Drafted | `design/marketplace-docs-free.md` and `design/marketplace-docs-pro.md`. Each draft names a screenshot and alt text for each place. |

### Excellence Verified badge

| ID | Asked for | Free | Pro | Evidence or next step |
| --- | --- | --- | --- | --- |
| E1 | Secure, compatible and maintained by QIT's measure, plus a 4.0 rating with enough reviews | Later | Later | The HPOS declaration is honest and the "tested up to" headers are current (WordPress 7.1, WooCommerce 11.1). A test package and real reviews come after launch. |

## Notes that are not requirements

- **Spelling in the interface: decided.** Daniel chose English (UK) on 7 October 2026, so these stay as they are. Three strings a shopkeeper sees use British spelling: "From your catalogue" and "Note, such as colour or size (optional)" in the free plugin, and "Catalogue products are at today's prices" in an order note from the add-on. The style guide's American English rule is written for product pages and documentation, so this is not a stated requirement. WordPress plugins are normally written in American English and translated from there. Changing them is a small release for both plugins. It is your product's voice, so it is your call.
- **A demo without a server.** A WordPress Playground link could show the free plugin's order screen in a browser. It has not been tried, and it would not work for the add-on while its repository is private.
- **The icon is shared.** One icon serves both submissions, since the Marketplace joins them on one product page.

## Timeline, cycle 1, 7 October 2026

| Stage | What happened | Result |
| --- | --- | --- |
| Note taking | Read all 12 pages and three linked pages from the docs' source. Wrote the requirement list above. | 36 requirement rows. |
| Reproduce | No defect was reported, so there was nothing to reproduce. The baseline was a check of both 1.0.2 packages against the list. | Two package-level gaps: no `changelog.txt` in the free ZIP, and an icon that broke the corner and safe-area rules. |
| Search existing artifacts | See the prior-art check below. | Fork: extend what is already in the two repositories. |
| Build | Wrote `tests/package.php` first and watched it fail on the free 1.0.2 build with five failures. Added `changelog.txt`, set the free plugin to 1.0.3, and added the same test to the add-on. Rebuilt the icon from the existing artwork. | Local changes only. |
| Refute | Looked for what the change missed. Found that the test did not compare the version constant with the header, added that check, and watched it fail on a copy with a stale constant. | One gap found and closed. |
| Evidence | See Verification below. | All local checks pass on the 1.0.3 ZIP. |
| UAT | See UAT below. | Formal UAT not applicable. Routine check done. |
| Owner decision | Daniel said "Go" to committing both repositories, pushing, and releasing free 1.0.3. | Recorded as given. It is a go-ahead for those actions. |
| Release | Ran the checks again on the ZIP, committed and pushed the free repository at `203f4f8`, published v1.0.3 with the ZIP, and downloaded it back. | The downloaded ZIP has the same sha256 as the one tested. The tag points at `203f4f8`. |

## Cycle 2, 7 October 2026: listing text and documentation

| Stage | What happened | Result |
| --- | --- | --- |
| Note taking | Daniel asked for English (UK) spelling and for drafts of the listing text and the documentation. | The interface strings stay as they are. The drafts use UK spelling, against the style guide's American English rule. That is recorded as his decision. |
| Reproduce | Not applicable: new writing, no defect. | The baseline is rows L3, L5, L6, L7 and L10 above, which were "Not met". |
| Search existing artifacts | The two `readme.txt` files, both `README.md` files and the plugins' own interface strings. | Fork: the readmes are the base, rewritten to the Marketplace's shape. |
| Build | Wrote `design/marketplace-listing.md`, `design/marketplace-docs-free.md` and `design/marketplace-docs-pro.md`. | Three drafts, each ending with a table of which file backs which claim. |
| Evidence | Checked the steps against the scripts and classes, which corrected two details in the free draft: where "From your catalogue" appears, and what Enter does after a name. Counted the search title (54 characters) and meta description (139). Checked for dashes, emojis, outside links and headings below H3: none. | The drafts match the code at free 1.0.3 and Pro 1.0.2. |
| UAT | Not applicable: drafts for Daniel to read. | Waiting for his read. |

Left open by the drafts: no search data exists, so no search terms are targeted and the keyword list is a guess. The speed answer in the FAQs comes from where the files load, not from a measurement. The add-on has no screenshots. The drafts were committed with this file on Daniel's instruction the same day.

## Cycle 3, 7 October 2026: gallery images for the add-on

| Stage | What happened | Result |
| --- | --- | --- |
| Note taking | Daniel asked for the add-on's gallery screenshots. | Row L4 was "Not met" for the add-on. |
| Search existing artifacts | The free repository's `design/screenshots.mjs`. | Fork: the same capture method, with the add-on's own steps. |
| Build | Wrote `design/screenshots.mjs`. It seeds the sample shop, types two sets of saved terms into the setting, adds a typed item and a 10% discount, picks saved terms, saves typed items as drafts, repeats the order and marks the new one as paid, with real mouse and key events. Menus are chosen by label, because a menu's list opens outside the page. | Seven images in `design/gallery/`, 1280 x 860. |
| Evidence | The first run was on the usual test store. Its other plugins put their own boxes and notices in the pictures, and the last step timed out because the script expected the wrong wording of the paid note. Fixed both and ran again on a store with only WooCommerce 11.1.2 and the two plugins. All seven were opened and checked against their alt text. | Each image shows what its alt text says. |
| UAT | Not applicable: images for Daniel to look at. | Waiting for his look. |

Left open: no image shows the Order actions menu open. The images were committed with this file on Daniel's instruction the same day.

## Prior-art check

```
Prior-art check: full notes on the Woo Marketplace docs, with each requirement checked against both plugins

Candidates (closest first):
- This repository, design/woocommerce-marketplace-notes.md: earlier notes from four of the pages, with draft testing instructions. Covers part of the job.
- Free repository, design/wordpress-org-submission.md: the same kind of record for WordPress.org. A pattern to follow.
- Free repository, .wordpress-org/icon.svg and design/icon.html: the artwork and the way icons are rendered. Reused for the Marketplace icon.
- Skill library, reviewing-woocommerce-plugin-quality: owns the QIT route and the local preflight. Used for the checks.
- Skill library, developing-wordpress: owns the WordPress.org directory route. Nothing for the Woo Marketplace.
- Public skill packs and the wider web: not searched. Tracker (Plane): not reachable from this session.

Verdict: fork. Extend the existing notes and artwork in the two repositories.
Base: design/woocommerce-marketplace-notes.md and .wordpress-org/icon.svg
```

## Changes in this cycle

Free repository:

- `changelog.txt`, new, shipped in the ZIP.
- Version 1.0.3 in the plugin header, the version constant, and `readme.txt` (Stable tag, changelog, upgrade notice). The code is the same as 1.0.2.
- `tests/package.php`, new, not shipped.
- `design/marketplace-icon.svg` and `design/marketplace-icon-160x160.png`, new, not shipped.
- `README.md`: one line on 1.0.3 and one on the package test.

This repository:

- `tests/package.php`, new, not shipped.
- `design/marketplace-icon.svg`, new, and `design/marketplace-icon-160x160.png`, replaced.
- `README.md`: one line on the package test.
- This file.

The add-on's ZIP is unchanged at 1.0.2. The free repository's changes are commit `203f4f8`, released as v1.0.3. This repository's changes went in with this file.

## Verification

Run on 7 October 2026 against the free 1.0.3 ZIP, sha256 `0b7caafa8cd33c6c560601cbe76b6b7c4253760a637663fd7ae9ca32338a6204`, 13 files. The package test, the preflight and both test suites were run a second time just before the release, on a rebuild with the same sha256.

| Check | Result |
| --- | --- |
| `php tests/package.php` on the 1.0.2 build | 5 failed, as expected before the change |
| `php tests/package.php` on the unzipped 1.0.3 ZIP | All 8 passed |
| The same test on the add-on's 1.0.2 build | All 8 passed. It fails with 5 failures on a copy with the changelog removed. |
| `package_check.py` on the ZIP | root, dev, version and domain all pass. After the commit, all 13 files match it too. |
| Local QIT-style preflight | 8 PASS, 0 WARN, 0 FAIL, 0 SKIPPED |
| `tests/run.php` and the add-on's `tests/run.php` | 107 of 107 and 64 of 64 on WordPress 7.1.3 with WooCommerce 11.1.2 and PHP 8.5.10, HPOS, with free 1.0.3 and Pro 1.0.2 |
| Activation run against the control store | PASS, no log entry that the control store lacks |
| Plugin Check 2.1.0 on free 1.0.3 | No errors, no warnings. 29 checks ran. The same five runtime checks cannot run in Playground. |
| Icon measurement | 160 x 160, square corners, artwork inside the safe area |

Not run for 1.0.3: the older stack (WordPress 6.8.11 with WooCommerce 10.0.6), post-based order storage, and a hand click-through. The PHP, script and stylesheet are the same files as 1.0.2 apart from the version number, and 1.0.2 was tested on those.

## UAT

The acceptance skill calls its user panels oversized when the release does not change behaviour, carry data across an upgrade, or move money. That fits here: the plugin's code is unchanged, and the cycle added a text file, a test, an icon and these notes. So formal UAT is recorded as **not applicable**, and the routine completion check above stands in for it. No acceptance verdict is claimed. Daniel's "Go" approved the commit, push and release.

## Open items and owners

| Item | Owner | Next step |
| --- | --- | --- |
| Partner account, agreement, business details, support email | Daniel | woocommerce.com/partners |
| Name, price, donate link, highlight colour | Daniel | Decide before the listing text is written. |
| Product page text, FAQs and documentation for both | Daniel | Drafted in English (UK). Read them, then paste them into the vendor dashboard once the account is approved. If the name changes, replace it throughout. |
| The six popular extensions | Daniel to supply copies, then agent | Test each beside both plugins. |
| QIT results | Daniel to submit, then agent | Read them from the submission and fix what fails. |
| Tracker entry in Plane | Daniel, or an agent with Plane access | None was read or written from this session. |

## Journal entry (draft, not filed)

The only journal found is your personal day planner in Drive, which has its own template, so nothing was written there. This is the entry, ready to paste wherever you want it.

```
date: 2026-10-07
category: side
tags: [woocommerce, marketplace, inline-order-editor]

What I worked on
- Read the whole Woo Marketplace docs section (12 pages) and checked both Inline Order Editor plugins against it. Record: IOE-MKT-1, design/woo-marketplace-docs-notes.md in the Pro repository.
- Fixed two package-level gaps: the free plugin now ships changelog.txt (released as 1.0.3), and the Marketplace icon follows the square-corner and safe-area rules.

What I learned
- The Marketplace rejects an upload with no changelog.txt, a badly formatted one, or a version that differs between the header, the upload form and the changelog.
- The product icon must not have rounded corners, and its artwork has to sit inside the middle 112 x 112 of a 160 x 160 image.
- The listing and documentation have to be in American English with no outside links. The readmes are in British English.
- A free plugin is only accepted with a paid path, as two submissions.

Patterns and flags
- An asset made before its rules were read broke two of them. Read the destination's asset page before drawing.
- The session's first plan would have submitted a ZIP the Marketplace rejects. A package test now guards the changelog and the version.

Open threads
- Free 1.0.3 is released (v1.0.3, commit 203f4f8). WordPress.org should get this ZIP, not 1.0.2.
- Partner account, name, price, donate link and interface spelling are Daniel's decisions.
- Listing text, FAQs, documentation and Pro gallery images are not drafted.
```
