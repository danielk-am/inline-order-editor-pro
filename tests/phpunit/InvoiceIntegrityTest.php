<?php
use PHPUnit\Framework\TestCase;

final class InvoiceIntegrityTest extends TestCase {
    private $orders = array();
    private $keys = array();
    private $users = array();
    private $settings;
    private $counters = array();
    protected function setUp(): void {
        $this->assertTrue( class_exists( 'Alkamy_Invoice_Delivery' ), 'Activate Pro invoicing.' );
        foreach ( array( 'alkamy_invoice_counter_' . wp_date( 'Ym' ), 'alkamy_document_counter_quotation_' . wp_date( 'Ym' ), 'alkamy_document_counter_delivery_order_' . wp_date( 'Ym' ), 'alkamy_document_counter_packing_slip_' . wp_date( 'Ym' ) ) as $key ) { $this->counters[$key] = get_option( $key, null ); }
        $this->settings = get_option( 'alkamy_invoice_delivery', null );
        update_option( 'alkamy_invoice_delivery', array( 'enabled' => true, 'email_ids' => array( 'customer_processing_order', 'customer_completed_order' ) ) );
    }
    protected function tearDown(): void {
        Alkamy_Invoice_Delivery::cleanup();
        wp_set_current_user( 0 );
        foreach ( $this->counters as $key => $value ) { if ( null === $value ) { delete_option( $key ); } else { update_option( $key, $value, false ); } }
        foreach ( $this->orders as $order ) {
            $order->read_meta_data( true );
            foreach ( Alkamy_Document_Numbers::read( $order ) as $type => $number ) {
                if ( $number ) { delete_option( ( 'invoice' === $type ? 'alkamy_invoice_claim_' : 'alkamy_document_claim_' . $type . '_' ) . hash( 'sha256', $number ) ); }
                delete_option( 'alkamy_document_reservation_' . $type . '_' . $order->get_id() );
            }
            delete_option( 'alkamy_document_lock_' . $order->get_id() );
            foreach ( array( 'alkamy_issued_invoice_', 'alkamy_invoice_reservation_', 'alkamy_invoice_lock_' ) as $prefix ) { delete_option( $prefix . $order->get_id() ); }
            $order->delete( true );
        }
        foreach ( $this->keys as $key ) { delete_option( $key ); }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ( $this->users as $id ) { wp_delete_user( $id ); }
        if ( null === $this->settings ) { delete_option( 'alkamy_invoice_delivery' ); } else { update_option( 'alkamy_invoice_delivery', $this->settings ); }
    }
    private function order() {
        $order = wc_create_order( array( 'status' => 'pending' ) );
        $this->orders[] = $order;
        return $order;
    }
    private function number() {
        $number = 'PHPUNIT-' . wp_generate_uuid4();
        $this->keys[] = 'alkamy_invoice_claim_' . hash( 'sha256', $number );
        return $number;
    }
    private function archive( $order ) {
        return Alkamy_Invoice_Delivery::import_legacy( $order, $this->number(), "%PDF-1.4\nPHPUnit immutable fixture\n%%EOF", 'phpunit' );
    }
    public function testSingaporeIdentifiersFollowStoreBaseCountryAndPreserveHiddenValues(): void {
        $country = get_option( 'woocommerce_default_country' );
        $post = $_POST;
        $user = wp_insert_user( array( 'user_login' => 'alkamy-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'administrator' ) );
        $this->users[] = $user;
        wp_set_current_user( $user );
        $order = $this->order();
        $order->update_meta_data( '_alkamy_buyer_uen', 'TEST-UEN' );
        $order->update_meta_data( '_alkamy_buyer_peppol', '0195:TEST' );
        $order->save();
        try {
            update_option( 'woocommerce_default_country', 'SG' );
            ob_start(); Alkamy_Workspace::identifiers( $order ); $sg = ob_get_clean();
            $this->assertStringContainsString( 'name="buyer_uen"', $sg );
            $this->assertStringContainsString( 'name="buyer_peppol"', $sg );
            update_option( 'woocommerce_default_country', 'US:CA' );
            ob_start(); Alkamy_Workspace::identifiers( $order ); $us = ob_get_clean();
            $this->assertSame( '', $us );
            $_POST = array( '_alkamy_sections_nonce' => wp_create_nonce( 'alkamy_order_sections' ) );
            Alkamy_Workspace::save( $order->get_id() );
            $saved = wc_get_order( $order->get_id() );
            $this->assertSame( 'TEST-UEN', $saved->get_meta( '_alkamy_buyer_uen' ) );
            $this->assertSame( '0195:TEST', $saved->get_meta( '_alkamy_buyer_peppol' ) );
            update_option( 'woocommerce_default_country', 'SG' );
            $_POST['buyer_uen'] = ''; $_POST['buyer_peppol'] = '';
            Alkamy_Workspace::save( $order->get_id() );
            $saved = wc_get_order( $order->get_id() );
            $this->assertSame( '', $saved->get_meta( '_alkamy_buyer_uen' ) );
            $this->assertSame( '', $saved->get_meta( '_alkamy_buyer_peppol' ) );
        } finally {
            update_option( 'woocommerce_default_country', $country );
            $_POST = $post;
        }
    }
    public function testDuplicateNumberValidationIsAtomicAcrossFields(): void {
        $first = $this->order(); $second = $this->order(); $number = $this->number();
        Alkamy_Document_Numbers::apply( $first, array( 'quotation' => $number ) ); $first->save();
        try {
            Alkamy_Document_Numbers::apply( $second, array( 'invoice' => $this->number(), 'quotation' => $number ) );
            $this->fail( 'Duplicate quotation was accepted.' );
        } catch ( InvalidArgumentException $error ) {
            $this->assertSame( '', Alkamy_Document_Numbers::read( $second )['invoice'] );
            $this->assertSame( '', Alkamy_Document_Numbers::read( $second )['quotation'] );
        }
    }
    public function testArchiveCannotBeOverwrittenByChangedLegacyPdf(): void {
        $order = $this->order(); $archive = $this->archive( $order );
        try {
            Alkamy_Invoice_Delivery::import_legacy( $order, $archive['number'], '%PDF-different', 'phpunit' );
            $this->fail( 'Archive overwrite was accepted.' );
        } catch ( RuntimeException $error ) {
            $this->assertSame( $archive, Alkamy_Invoice_Delivery::get_issued( $order->get_id() ) );
        }
    }
    public function testIssuedNumberCannotBeCleared(): void {
        $order = $this->order(); $archive = $this->archive( $order );
        $order = wc_get_order( $order->get_id() );
        $this->expectException( InvalidArgumentException::class );
        Alkamy_Document_Numbers::apply( $order, array( 'invoice' => '' ) );
    }
    public function testDamagedArchiveFailsClosed(): void {
        $order = $this->order(); $archive = $this->archive( $order );
        $archive['pdf'] = base64_encode( '%PDF-tampered' );
        update_option( 'alkamy_issued_invoice_' . $order->get_id(), $archive, false );
        $this->expectException( RuntimeException::class );
        Alkamy_Invoice_Delivery::bytes( $order->get_id() );
    }
    public function testInsertOnlyClaimCannotReplaceExistingOwner(): void {
        $key = 'alkamy_phpunit_' . wp_generate_uuid4(); $this->keys[] = $key;
        $this->assertTrue( Alkamy_Invoice_Delivery::atomic_add( $key, 123 ) );
        $this->assertFalse( Alkamy_Invoice_Delivery::atomic_add( $key, 456 ) );
        $this->assertSame( 123, (int) get_option( $key ) );
        global $wpdb;
        $this->assertSame( 'off', $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $key ) ) );
    }
    public function testEmailHooksReuseSamePrivatePdfAndCleanupIsDeferred(): void {
        $order = $this->order(); $archive = $this->archive( $order );
        $first = apply_filters( 'woocommerce_email_attachments', array(), 'customer_processing_order', $order, null );
        $second = apply_filters( 'woocommerce_email_attachments', array(), 'customer_completed_order', $order, null );
        $this->assertCount( 1, $first ); $this->assertSame( $first, $second );
        $this->assertFileExists( $first[0] );
        $this->assertSame( $archive['sha256'], hash_file( 'sha256', $first[0] ) );
        $this->assertSame( 0600, fileperms( $first[0] ) & 0777 );
        Alkamy_Invoice_Delivery::cleanup();
        $this->assertFileDoesNotExist( $first[0] );
    }
    public function testUnselectedEmailLeavesExistingAttachmentsUntouched(): void {
        $order = $this->order();
        $this->assertSame( array( 'existing.pdf' ), Alkamy_Invoice_Delivery::attachments( array( 'existing.pdf' ), 'new_order', $order ) );
        $this->assertNull( Alkamy_Invoice_Delivery::get_issued( $order->get_id() ) );
    }
    public function testOwnerAccessIsIndependentOfBillingEmail(): void {
        $order = $this->order();
        $owner = wp_create_user( 'phpunit-' . wp_generate_uuid4(), wp_generate_password() ); $this->users[] = $owner;
        $other = wp_create_user( 'phpunit-' . wp_generate_uuid4(), wp_generate_password() ); $this->users[] = $other;
        $order->set_customer_id( $owner ); $order->save();
        wp_set_current_user( $owner ); $this->assertTrue( Alkamy_Customer_Documents::allowed( $order ) );
        wp_set_current_user( $other ); $this->assertFalse( Alkamy_Customer_Documents::allowed( $order ) );
        wp_set_current_user( 0 ); $this->assertFalse( Alkamy_Customer_Documents::allowed( $order ) );
        $order->set_customer_id( 0 ); wp_set_current_user( $owner );
        $this->assertFalse( Alkamy_Customer_Documents::allowed( $order ) );
    }
    public function testMissingReferencesAreAssignedOnceAndPersisted(): void {
        $order = $this->order();
        $numbers = Alkamy_Document_Numbers::auto_assign( $order );
        $this->assertCount( 4, array_filter( $numbers ) );
        $this->assertCount( 4, array_unique( $numbers ) );
        $this->assertStringStartsWith( 'QUOT-', $numbers['quotation'] );
        $this->assertStringStartsWith( 'DO-', $numbers['delivery_order'] );
        $this->assertStringStartsWith( 'PS-', $numbers['packing_slip'] );
        $this->assertSame( $numbers, Alkamy_Document_Numbers::read( wc_get_order( $order->get_id() ) ) );
        $this->assertSame( $numbers, Alkamy_Document_Numbers::auto_assign( $order ) );
        $this->assertNull( Alkamy_Invoice_Delivery::get_issued( $order->get_id() ), 'Reserving references must not issue a PDF.' );
    }
    public function testAssignedReferencesSurviveAndMissingSeriesRemainUnique(): void {
        $first = $this->order(); $second = $this->order();
        $manual = 'MANUAL-' . wp_generate_uuid4();
        Alkamy_Document_Numbers::apply( $first, array( 'quotation' => $manual ) ); $first->save();
        $a = Alkamy_Document_Numbers::auto_assign( $first );
        $b = Alkamy_Document_Numbers::auto_assign( $second );
        $this->assertSame( $manual, $a['quotation'] );
        foreach ( $a as $type => $number ) { $this->assertNotSame( $number, $b[$type], $type . ' must be unique across orders.' ); }
    }
    public function testRentalTimesUseStoreTimezoneAndIgnoreSubmittedTimezone(): void {
        $original = get_option( 'timezone_string' );
        update_option( 'timezone_string', 'Asia/Singapore' );
        try {
            $data = Alkamy_Rental_Order_Fields::clean( array( 'preserved' => 'value' ), array( 'am_method' => 'collection', 'am_location' => '', 'start' => '2026-11-01T09:00', 'return' => '2026-11-02T09:00', 'additional_info' => 'Test', 'timezone' => 'America/New_York' ) );
            $this->assertSame( 'Asia/Singapore', $data['timezone'] );
            $this->assertSame( '09:00', $data['am_start_time'] );
            $this->assertSame( 'value', $data['preserved'] );
        } finally { update_option( 'timezone_string', $original ); }
    }
    public function testClaimRemainsProtectedAfterOriginalReferenceChanges(): void {
        $first = $this->order(); $second = $this->order();
        $numbers = Alkamy_Document_Numbers::auto_assign( $first );
        $this->keys[] = 'alkamy_document_claim_quotation_' . hash( 'sha256', $numbers['quotation'] );
        Alkamy_Document_Numbers::apply( $first, array( 'quotation' => 'REPLACEMENT-' . wp_generate_uuid4() ) );
        $first->save_meta_data();
        $this->expectException( InvalidArgumentException::class );
        Alkamy_Document_Numbers::apply( $second, array( 'quotation' => $numbers['quotation'] ) );
    }
    public function testMissingSavedMetadataRecoversSameReservedReferences(): void {
        $order = $this->order();
        $reserved = Alkamy_Document_Numbers::auto_assign( $order );
        // Reproduce a failed CRUD persistence after durable number reservations.
        foreach ( array_keys( $reserved ) as $type ) { $order->delete_meta_data( '_alkamy_number_' . $type ); }
        $order->save_meta_data();
        $this->assertSame( array( 'invoice' => '', 'quotation' => '', 'delivery_order' => '', 'packing_slip' => '' ), Alkamy_Document_Numbers::read( wc_get_order( $order->get_id() ) ) );
        $this->assertSame( $reserved, Alkamy_Document_Numbers::auto_assign( wc_get_order( $order->get_id() ) ) );
    }
}
