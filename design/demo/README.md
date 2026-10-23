# Studio demo and media

The local **Alkamy Order Editor Demo** is registered in WordPress Studio at `http://localhost:8895`. Its fictional Petal & Stem shop has a pending order for editing, a separate order with an existing refund, two editable PDF layouts, a reusable care-note pattern, and sample company branding. Open WP Admin from Studio, then go to **WooCommerce > Orders** or **PDF layouts**.

The installed Pro copy is the Marketplace variant: referral and legacy-service promotional panels are omitted. This demo runs locally. It is not a published HTTPS demo or a payment-processing sandbox.

## Review the media

- `screenshots/pro-01-invoicing-workspace.png`: assigned document references, section editor, and paper preview.
- `screenshots/pro-02-sections.png`: order-specific Markdown content beside its preview.
- `screenshots/pro-03-layout-editor.png`: the real WordPress block editor for a PDF layout.
- `screenshots/pro-04-pdf-preview.png`: a rendered page of the actual exported invoice.
- `screenshots/pro-05-document-exports.png`: paired Preview and Download controls.
- `videos/pro-walkthrough.webm`: trimmed silent recording of section editing, adding and reordering.
- `videos/layout-editor.webm`: short overview of the loaded block editor.
- `videos/transcript.md`: text guide to the clips.
- `documents/`: actual invoices, quotations, packing slips, delivery orders, and an existing-refund credit note. All records are fictional.

The original silent screen recordings are WebM and play in current Chromium/Firefox browsers. They contain no account sign-in, password, gateway credentials, or real customer data. They have not been uploaded to a video host. WooCommerce.com accepts one embedded video per listing; choose and host the final cut before submission.

## Recreate a disposable Studio site

Use the installed Studio CLI to create a fresh site. Keep the original demo when making a second capture run.

```sh
studio create --path "$ALKAMY_DEMO_SITE" --name 'Alkamy Order Editor Demo' \
  --wp 7.1.3 --php 8.3 --runtime native --file-access site-directory \
  --skip-browser --skip-log-details
```

Set these environment variables before running the setup script:

| Variable | Value |
| --- | --- |
| `ALKAMY_DEMO_SITE` | Fresh Studio site's absolute directory |
| `ALKAMY_DEMO_PRIVATE` | Private absolute directory outside the site's web root |
| `ALKAMY_FREE_ZIP` | Free plugin's built ZIP |
| `ALKAMY_PRO_ZIP` | Pro `build/marketplace/inline-order-editor-pro.zip` |
| `ALKAMY_WOO_SOURCE` | WooCommerce plugin directory or official ZIP |
| `STUDIO_BIN` | Optional Studio executable for capture, otherwise `studio` |

```sh
bash design/demo/setup-studio.sh
```

The original demo was created and exercised using these component operations. Scripts use `studio wp` so commands use the site runtime and SQLite database. The combined portable setup script has passed syntax checks; a second fresh-site replay has not been run. Setup refuses a non-local home URL and a cookie directory within the public site. It creates standard short-lived WordPress sessions, stores cookies with mode `0600`, and never prints them. Never copy the private directory, WordPress database, or configuration into a public repository or Marketplace upload.

Mail, WordPress HTTP requests, gateways, and webhook delivery are disabled or intercepted by the demo-only MU plugin. Cron and automatic updates are disabled. These are application-level guards; they do not establish an OS-level firewall or complete council-UAT isolation.

## Recapture

Install Playwright in a development environment and its recording dependency using `playwright install ffmpeg`. Supply `PLAYWRIGHT_MODULE` as the absolute path to Playwright's `index.mjs`, and `CHROME_BIN` if using a different Chrome executable.

```sh
export ALKAMY_DEMO_ROOT="$ALKAMY_DEMO_SITE"
export ALKAMY_DEMO_MANIFEST="$ALKAMY_DEMO_PRIVATE/manifest.json"
export ALKAMY_DEMO_COOKIES="$ALKAMY_DEMO_PRIVATE/cookies.json"
node design/demo/capture.mjs
```

Keep the Free source repository beside Pro, or set `ALKAMY_FREE_REPO`. Pro's wrapper uses the shared capture script from Free. The capture mutates only the disposable fixture: it edits items, adds an item/section, saves terms, and reorders sections. Use a fresh seeded site for a clean replay. It deliberately activates Pro for this run and restores it afterward. Free's `ALKAMY_CAPTURE_MODE=all` captures both tiers, deactivating Pro during Free-only shots.

The capture receipt records actual checks and errors. The first browser attempt needed an accordion-state correction in the capture script; this was a harness issue, not a product failure. Retained delivery evidence distinguishes completed captures from earlier attempts. These recordings and local smoke checks are not Marketplace QIT or a completed hosted CI run.

## Capture corrections

The release gallery uses corrected editor and whole-widget screenshots. Editor onboarding and long navigation/export waits were removed from the Pro walkthrough. Layout-editor footage is a separate overview, not a claim that an edit/save cycle was recorded. Final media hashes are in the gallery manifest; raw capture receipts describe the original capture run before trimming.

## Narrated walkthrough edition

- `videos/pro-walkthrough-narrated.mp4`: H.264/AAC, 1600×900, actual browser UI with chapter titles, synthetic English narration, and burned-in sentence captions.
- `videos/pro-walkthrough-narrated.vtt` and `.srt`: matching caption files.
- `videos/pro-walkthrough-transcript.md`: chapter timestamps and complete narration.

Recorded on separate fictional orders, with the original demonstration order preserved. The voice is ElevenLabs Adam stock voice, generated with Eleven v4 through the existing signed-in workflow; it does not represent Daniel's voice. Navigation and loading waits between chapters are trimmed. Captions occupy a separate footer below the UI. The original silent recordings remain available.

This edition records an item-table spacing change, saves the cloned PDF layout, and verifies the selected value after reloading. Invoice download was verified in a separate capture; the final export chapter tours the paired controls without Chrome’s download-shelf artifact. No payment or email is sent. The earlier separate silent layout overview remains an overview only.
