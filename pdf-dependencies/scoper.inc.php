<?php
/** Build-only namespace isolation; PHP-Scoper never ships in the plugin. */
return array(
 'prefix'=>'AlkamyPdfVendor',
 'finders'=>array( Symfony\Component\Finder\Finder::create()->files()->in( getenv( 'ALKAMY_PDF_SOURCE' ) )->exclude( 'composer' )->notName( 'autoload.php' ) ),
 'expose-global-constants'=>false,
 'expose-global-classes'=>false,
 'expose-global-functions'=>false,
 'patchers'=>array( static function ( $file, $prefix, $content ) {
  // Upstream constructs these names dynamically; scope those strings too.
  foreach ( array( 'Dompdf', 'FontLib' ) as $namespace ) { $content = str_replace( '"' . $namespace . '\\\\', '"AlkamyPdfVendor\\\\' . $namespace . '\\\\', $content ); }
  $content = str_replace( "'\\Dompdf\\", "'\\AlkamyPdfVendor\\Dompdf\\", $content );
  if ( false !== strpos( $file, '/FontLib/Font.php' ) ) {
   foreach ( array( 'TrueType', 'OpenType', 'WOFF', 'EOT' ) as $namespace ) { $content = str_replace( '"AlkamyPdfVendor\\\\' . $namespace, '"' . $namespace, $content ); }
  }
  if ( false !== strpos( $file, '/FontLib/TrueType/File.php' ) ) { $content = str_replace( 'return $class_parts[1];', 'return $class_parts[2];', $content ); }
  return $content;
 } ),
);
