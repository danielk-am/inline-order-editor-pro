<?php
/** Verify the Marketplace distribution against its direct-distribution sibling. CLI only. */
if ( 'cli' !== PHP_SAPI ) { exit; }
$marketplace = realpath( $argv[1] ?? '' );
$direct = realpath( $argv[2] ?? '' );
if ( ! $marketplace || ! $direct || $marketplace === $direct ) {
 fwrite( STDERR, "Usage: php tests/marketplace-package.php MARKETPLACE_FOLDER DIRECT_FOLDER\n" ); exit( 1 );
}
$optional = array( 'modules/invoicing/includes/class-alkamy-direct-distribution.php' );
$failures = 0;
$checks = 0;
$check = static function ( $ok, $message ) use ( &$failures, &$checks ) {
 ++$checks; echo ( $ok ? 'PASS ' : 'FAIL ' ) . $message . "\n";
 if ( ! $ok ) { ++$failures; }
};
$files = static function ( $root ) {
 $result = array();
 foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
  if ( $file->isFile() ) { $result[ substr( $file->getPathname(), strlen( $root ) + 1 ) ] = hash_file( 'sha256', $file->getPathname() ); }
 }
 ksort( $result ); return $result;
};
$direct_files = $files( $direct );
$marketplace_files = $files( $marketplace );
foreach ( $optional as $file ) {
 $check( isset( $direct_files[ $file ] ), 'Direct distribution retains ' . $file );
 $check( ! isset( $marketplace_files[ $file ] ), 'Marketplace omits ' . $file );
 unset( $direct_files[ $file ] );
}
$check( ! is_file( $direct . '/includes/class-ioefwp-licensing.php' ) && ! is_file( $marketplace . '/includes/class-ioefwp-licensing.php' ), 'Neither distribution includes the retired commercial licensing client.' );
$check( $direct_files === $marketplace_files, 'Every other packaged file is byte-identical; stable identifiers and product features remain intact.' );
$offending = array();
foreach ( $marketplace_files as $relative => $hash ) {
 if ( ! preg_match( '/\.(php|js|css|json|txt|md|html)$/i', $relative ) ) { continue; }
 $source = file_get_contents( $marketplace . '/' . $relative );
 if ( preg_match( '~referral_code\s*=|automattic\.pxf\.io|DANIEXCBRX~i', $source ) || ( 'php' === pathinfo( $relative, PATHINFO_EXTENSION ) && preg_match( '~Legacy hosted document service|Optional setup with Alkamy~i', $source ) ) ) { $offending[] = $relative; }
}
$check( ! $offending, 'No affiliate URLs/codes, agency upsell or hosted-service promotional panel remain: ' . implode( ', ', $offending ) );
echo "$checks checks; $failures failures.\n";
exit( $failures ? 1 : 0 );
