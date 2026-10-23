# QIT setup and first-product gate

Checked 15 October 2026. Verdict: **PREFLIGHT_FINDINGS**. Managed QIT tests did not run.

The later [local quality pass](quality-2026-10-15.md) covers Free 1.0.10-alpha / Pro 1.3.4-alpha: all PCP warnings are resolved, the missing WooCommerce tested header is filled after real checks, and PHPUnit is clean. The preflight findings and hashes below describe the earlier packages; no new managed QIT result is implied.

## Installed and connected

QIT CLI 1.3.2 is installed in this task's `work/qit-tools/vendor/bin/qit`, using Composer 2.10.3 and PHP 8.3.33. Partner authentication succeeded. The user approved creation of the QIT connection and testing both packages. The token was transferred through an in-memory pipe into the CLI's protected terminal prompt. QIT's configuration directory is owner-only and credential-cache files have mode 0600. No token is in this repository or report.

`qit extensions` returned an empty table. An enqueue attempt for each exact product returned:

- Free: `Could not find Woo Extension with slug inline-order-editor-for-woocommerce.`
- Pro: `Could not find Woo Extension with slug inline-order-editor-pro.`

No managed run IDs were created. The vendor Submissions page also showed no submissions. Do not use an unrelated product ID to bypass this gate. The current documentation says CLI testing needs a partner account with a listed extension; first-product submissions trigger managed review through the vendor submission workflow. Registration/submission remains the next product-owner step. No product submission or agreement acceptance was performed.

## Exact packages

- Free 1.0.9-alpha: `b1267dc3b1919034fc80ffcdb156216999dbb7787e04e4baadbf51255e6d4814`
- Marketplace Pro 1.3.3-alpha: `f1be56a33b56a09127d8eef511a14ccabb2e6c088d082db9e4dee87f090fcfb8`

Both passed the actual QIT CLI `woo:validate-zip` command. This validates archive content; it is not the managed Validation test.

## Local preflight, not QIT

The exact ZIPs were unpacked outside the repositories and scanned. Free: 6 PASS, 2 SKIPPED. Pro: 1 PASS, 2 WARN, 3 FAIL, 2 SKIPPED. Semgrep and gitleaks were unavailable and did not run. Neither result is a QIT pass.

Pro triage:

- Metadata: `WC tested up to` is missing. Fill this with a genuinely tested release before the next candidate.
- Malware heuristics: flags occur in shipped upstream PDF libraries, including Unicode entity/parser tables, asserts and Dompdf's eval-capable APIs. PHP evaluation is disabled by the wrapper; the string page-script APIs still need explicit review. No blanket suppression or vendor edit was made.
- PHPCS: all 176 errors are in bundled libraries. Own-code results are eight warnings for base64 document/image transport and local file operations. These need contextual review rather than a claim of compromise or universal false positives.
- PHPCompatibility: the open-ended range reports 91 errors, including future PHP 8.6 trim behavior. Repeating with the installed QIT CLI's current maximum, PHP 8.5, leaves seven errors in bundled Dompdf. The SVG constants have explicit `defined` guards; the GD adapter and HTML entity defaults need further compatibility triage. This does not establish a runtime failure in the configured CPDF backend.
- PHPStan: 438 messages, mostly bundled-library analysis, with 13 own-code messages involving optional classes and inferred types. Needs scoped triage.
- The generic preflight's top-level dependency detector missed nested PDF vendor files. A separate locked Composer audit of `pdf-dependencies` returned no advisories or abandoned packages. That closure also includes build tools; it is not a substitute for managed Security.

Tools: PHPCS 4.0.4; WPCS dev-phpcs-4; PHPCompatibility dev-develop; PHPCompatibilityWP 3.0.0-alpha2; PHPStan 2.3.1. Original logs/JSON live under this task's `work/qit-release/`.

## Managed scope

| Test | State |
| --- | --- |
| Security | Not enqueued, both product slugs unavailable |
| Validation | Not run, same product-registration gate |
| Malware | Not run, same gate |
| PHPCompatibility | Not run, same gate |
| Activation | Not run, same gate |
| Woo API | Not run, same gate |
| Woo E2E | Not run, same gate |

Studio browser smoke, PHPUnit and Plugin Check evidence remain separate. No managed or local-QIT environment was started. Docker is not installed on this Mac; managed remote tests do not require local Docker.

Sources read: https://qit.woo.com/docs/llms.txt , https://qit.woo.com/docs/getting-started/ , https://qit.woo.com/docs/managed-tests/ and its Security, Validation, Malware, PHPCompatibility, Activation, Woo API and Woo E2E pages; https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product/ . Installed CLI help was checked. The getting-started page describes a browser flow; installed CLI 1.3.2 specifically prompts for the partner username/email and a QIT token.
