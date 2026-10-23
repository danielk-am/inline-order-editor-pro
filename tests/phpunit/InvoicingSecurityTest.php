<?php
use PHPUnit\Framework\TestCase;

/** Exercises the security boundaries with real WordPress attachment and rendering APIs. */
final class InvoicingSecurityTest extends TestCase {
    private $files = array();
    private $attachments = array();
    private $user;
    private $settings;
    protected function setUp(): void {
        $this->user = get_current_user_id();
        $this->settings = get_option( 'alkamy_invoice_delivery', null );
        wp_set_current_user( 1 );
    }
    protected function tearDown(): void {
        foreach ( $this->attachments as $id ) { wp_delete_attachment( $id, true ); }
        foreach ( $this->files as $file ) { if ( is_file( $file ) ) { unlink( $file ); } }
        if ( null === $this->settings ) { delete_option( 'alkamy_invoice_delivery' ); } else { update_option( 'alkamy_invoice_delivery', $this->settings ); }
        wp_set_current_user( $this->user );
    }
    private function image( $mime = 'png', $width = 1 ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $file = wp_tempnam( 'alkamy-security-' ); $this->files[] = $file;
        $image = imagecreatetruecolor( $width, 1 );
        if ( 'jpeg' === $mime ) { imagejpeg( $image, $file ); } else { imagepng( $image, $file ); }
        imagedestroy( $image );
        $id = wp_insert_attachment( array( 'post_mime_type' => 'image/' . $mime, 'post_title' => 'Security fixture', 'post_status' => 'inherit' ), $file );
        $this->attachments[] = $id;
        return array( $id, $file );
    }
    public function testRasterRetainsLocalPngAndJpegAndRejectsLimits(): void {
        foreach ( array( 'png', 'jpeg' ) as $mime ) {
            list( $id, $file ) = $this->image( $mime );
            $this->assertSame( 'data:image/' . $mime . ';base64,' . base64_encode( file_get_contents( $file ) ), Alkamy_Layout::raster( $id ) );
        }
        list( $id, $file ) = $this->image( 'png', 2001 );
        $this->assertSame( '', Alkamy_Layout::raster( $id ) );
        list( $id, $file ) = $this->image( 'png', 2000 );
        file_put_contents( $file, str_repeat( 'x', 262144 - filesize( $file ) ), FILE_APPEND ); clearstatcache( true, $file );
        $this->assertStringStartsWith( 'data:image/png;base64,', Alkamy_Layout::raster( $id ) );
        file_put_contents( $file, 'x', FILE_APPEND ); clearstatcache( true, $file );
        $this->assertSame( '', Alkamy_Layout::raster( $id ) );
        $this->assertSame( '', Alkamy_Layout::raster( 0 ) );
    }
    public function testFilteredWrappersAreRejectedBeforeFilesystemOperations(): void {
        list( $id ) = $this->image();
        stream_wrapper_register( 'alkamyprobe', Alkamy_Security_Stream::class );
        try {
            foreach ( array( 'alkamyprobe://payload', 'phar://archive/logo.png', 'PHAR://archive/logo.png', 'file:///tmp/logo.png', 'https://example.invalid/logo.png', "bad\0path", array( 'invalid' ) ) as $path ) {
                $filter = static function () use ( $path ) { return $path; };
                add_filter( 'get_attached_file', $filter );
                try { $this->assertSame( '', Alkamy_Layout::raster( $id ) ); } finally { remove_filter( 'get_attached_file', $filter ); }
            }
            $this->assertSame( 0, Alkamy_Security_Stream::$touches );
        } finally { stream_wrapper_unregister( 'alkamyprobe' ); }
    }
    public function testBrandSaveUsesTheSameStreamBoundary(): void {
        list( $id ) = $this->image();
        $post = $_POST; $request = $_REQUEST;
        $filter = static function () { return 'alkamyprobe://payload'; };
        $die = static function () { return static function ( $message, $title, $args ) { throw new RuntimeException( $message, (int) $args['response'] ); }; };
        stream_wrapper_register( 'alkamyprobe', Alkamy_Security_Stream::class );
        Alkamy_Security_Stream::$touches = 0;
        add_filter( 'get_attached_file', $filter ); add_filter( 'wp_die_handler', $die );
        try {
            $_POST = array( 'brand' => array( 'logo_id' => $id ) );
            $_REQUEST['_wpnonce'] = wp_create_nonce( 'alkamy_document_brand' );
            try { Alkamy_Layout_Editor::save(); $this->fail( 'Invalid logo was accepted.' ); }
            catch ( RuntimeException $error ) {
                $this->assertSame( 400, $error->getCode() );
                $this->assertStringContainsString( 'Choose a PNG or JPEG logo', $error->getMessage() );
            }
            $this->assertSame( 0, Alkamy_Security_Stream::$touches );
        } finally {
            $_POST = $post; $_REQUEST = $request;
            remove_filter( 'get_attached_file', $filter ); remove_filter( 'wp_die_handler', $die );
            stream_wrapper_unregister( 'alkamyprobe' );
        }
    }
    public function testStoredSettingsAndBlockAttributesCannotInjectMarkup(): void {
        $attack = '</script><script>alert("x")</script><img src=x onerror=alert(1)>';
        update_option( 'alkamy_invoice_delivery', array( 'enabled' => true, 'email_ids' => array( 'customer_invoice' ), 'numbering_prefix' => $attack, 'number_padding' => '" onfocus="alert(1)' ) );
        ob_start(); Alkamy_Invoice_Settings::render(); $html = ob_get_clean();
        $this->assertStringNotContainsString( '<script>', $html );
        $this->assertStringNotContainsString( 'onfocus="alert', $html );
        $scripts = implode( '', wp_scripts()->get_data( 'alkamy-invoice-settings', 'before' ) );
        $this->assertStringNotContainsString( '</script', $scripts );
        $this->assertStringContainsString( '\\u003C', $scripts );
        $attrs = array( 'part' => 'paragraph', 'text' => 'Merchant text ' . $attack, 'textAlign' => 'right', 'style' => array( 'color' => array( 'text' => 'red; background:url(javascript:alert(1))' ) ), 'className' => '" onmouseover="alert(1)' );
        // Exercise the registered block so WordPress supplies its normal render context.
        $html = render_block( array( 'blockName' => 'alkamy/document-part', 'attrs' => $attrs, 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
        $this->assertStringNotContainsString( '<script>', $html );
        $this->assertStringNotContainsString( '<img src=x', $html );
        $this->assertStringNotContainsString( 'url(javascript:', $html );
        // An escaped class value may contain the literal text; reject actual event-handler attributes.
        $document = new DOMDocument();
        $this->assertTrue( $document->loadHTML( '<!doctype html><html><body>' . $html . '</body></html>' ) );
        $xpath = new DOMXPath( $document );
        $events = $xpath->query( '//@*[starts-with(translate(name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "on")]' );
        $this->assertInstanceOf( DOMNodeList::class, $events );
        $this->assertSame( 0, $events->length );
        $this->assertStringContainsString( 'Merchant text', $html );
        $this->assertStringContainsString( 'text-align:right', $html );
        wp_set_current_user( 0 );
        $this->assertSame( '', Alkamy_Layout_Editor::render( $attrs ) );
    }
}
final class Alkamy_Security_Stream {
    public $context;
    public static $touches = 0;
    public function url_stat( $path, $flags ) { self::$touches++; return false; }
}
