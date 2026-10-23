<?php
/** Add per-file modification notices to mechanically scoped upstream PHP sources. */
if ( 'cli' !== PHP_SAPI ) { exit; }
$root = $argv[1] ?? '';
if ( ! is_dir( $root ) ) { fwrite( STDERR, "Usage: php bin/notice-pdf-dependencies.php VENDOR_DIRECTORY\n" ); exit( 1 ); }
$notice = 'Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained.';
$count = 0;
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
 if ( ! $file->isFile() || 'php' !== $file->getExtension() || $file->getPathname() === $root . '/autoload.php' ) { continue; }
 $source = file_get_contents( $file->getPathname() );
 if ( false === strpos( $source, 'AlkamyPdfVendor' ) || false !== strpos( $source, $notice ) ) { continue; }
 if ( 0 !== strpos( $source, '<?php' ) ) { throw new RuntimeException( 'Unexpected PHP opening: ' . $file->getPathname() ); }
 $source = substr_replace( $source, "\n/* " . $notice . " */", 5, 0 );
 if ( false === file_put_contents( $file->getPathname(), $source ) ) { throw new RuntimeException( 'Unable to add notice.' ); }
 ++$count;
}
echo $count . " scoped PHP files received notices.\n";
