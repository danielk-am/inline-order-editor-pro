<?php
/** Isolated PSR-4 loader for the bundled, unmodified scoped PDF dependencies. */
spl_autoload_register( static function ( $class ) {
 if ( 'AlkamyPdfVendor\\Dompdf\\Cpdf' === $class ) { require __DIR__ . '/dompdf/dompdf/lib/Cpdf.php'; return; }
 $prefixes = array(
  'AlkamyPdfVendor\\Dompdf\\' => __DIR__ . '/dompdf/dompdf/src/',
  'AlkamyPdfVendor\\FontLib\\' => __DIR__ . '/dompdf/php-font-lib/src/FontLib/',
  'AlkamyPdfVendor\\Svg\\' => __DIR__ . '/dompdf/php-svg-lib/src/Svg/',
  'AlkamyPdfVendor\\Sabberworm\\CSS\\' => __DIR__ . '/sabberworm/php-css-parser/src/',
  'AlkamyPdfVendor\\Masterminds\\' => __DIR__ . '/masterminds/html5/src/',
 );
 foreach ( $prefixes as $prefix => $directory ) {
  if ( 0 !== strpos( $class, $prefix ) ) { continue; }
  $suffix = substr( $class, strlen( $prefix ) );
  if ( ! preg_match( '/^[A-Za-z0-9_\\\\]+$/D', $suffix ) ) { return; }
  $file = $directory . str_replace( '\\', '/', $suffix ) . '.php';
  if ( is_file( $file ) ) { require $file; }
  return;
 }
} );
