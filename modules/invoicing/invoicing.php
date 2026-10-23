<?php
/** Bundled invoicing module; not a standalone plugin. */
defined( 'ABSPATH' ) || exit;
define( 'ALKAMY_INVOICING_FILE', __FILE__ );
require_once __DIR__ . '/includes/class-alkamy-service.php';
require_once __DIR__ . '/includes/class-alkamy-documents.php';
require_once __DIR__ . '/includes/class-alkamy-admin.php';
// Marketplace packages intentionally omit this optional direct-distribution UI.
if ( is_readable( __DIR__ . '/includes/class-alkamy-direct-distribution.php' ) ) {
 require_once __DIR__ . '/includes/class-alkamy-direct-distribution.php';
 Alkamy_Direct_Distribution::init();
}
require_once __DIR__ . '/includes/class-alkamy-workspace.php';
require_once __DIR__ . '/includes/class-alkamy-order-enhancements.php';
require_once __DIR__ . '/includes/class-alkamy-rental-order-fields.php';
require_once __DIR__ . '/includes/class-alkamy-payment-metadata.php';
require_once __DIR__ . '/includes/class-alkamy-layout.php';
require_once __DIR__ . '/includes/class-alkamy-layout-editor.php';
require_once __DIR__ . '/includes/class-alkamy-document-numbers.php';
require_once __DIR__ . '/includes/class-alkamy-local-pdf.php';
require_once __DIR__ . '/includes/class-alkamy-invoice-delivery.php';
require_once __DIR__ . '/includes/class-alkamy-customer-documents.php';
require_once __DIR__ . '/includes/class-alkamy-invoice-legacy.php';
require_once __DIR__ . '/includes/class-alkamy-invoice-settings.php';
Alkamy_Layout_Editor::init();
Alkamy_Admin::init();
Alkamy_Workspace::init();
// Pro owns the existing native terms picker; avoid a second dropdown in standard mode.
remove_action( 'ioefw_terms_box_actions', array( 'Alkamy_Workspace', 'terms_presets' ) );
Alkamy_Order_Enhancements::init();
Alkamy_Rental_Order_Fields::init();
Alkamy_Payment_Metadata::init();

Alkamy_Invoice_Delivery::init();
Alkamy_Customer_Documents::register();
Alkamy_Invoice_Legacy::register();
Alkamy_Invoice_Settings::init();
