#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
for test in licensing customer-document-access document-branding document-layout document-numbers document-service-gate invoice-delivery invoicing-loader invoicing-output-boundaries invoicing-security legacy-document-migration local-document-request local-pdf local-pdf-snapshot rental-order-fields; do
  php "tests/$test.php"
done
php tests/invoicing-loader.php missing-base
php tests/invoicing-loader.php standalone
