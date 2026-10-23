<?php
// protects=all official PCP findings block release; fails_when=private warnings are waived or a malformed report passes.
if ( 'cli' !== PHP_SAPI ) { exit; }
$report = tempnam( sys_get_temp_dir(), 'alkamy-pcp-' );
$warning = array( 'type' => 'WARNING', 'code' => 'plugin_header_requires_plugins_not_in_directory', 'message' => 'The plugin declares "inline-order-editor-for-woocommerce" as a required plugin, but it could not be found in the WordPress.org plugin directory.' );
$cases = array(
 array( "Success: Checks complete. No errors found.\n", 0, 'clean official report' ),
 array( json_encode( array( $warning ) ), 1, 'private dependency warning' ),
 array( json_encode( array( array( 'type' => 'WARNING', 'code' => 'other', 'message' => 'Other warning' ) ) ), 1, 'unrecognized warning' ),
 array( json_encode( array( array( 'type' => 'ERROR', 'code' => 'error', 'message' => 'Error' ) ) ), 1, 'error' ),
 array( '{}', 1, 'object instead of findings array' ),
 array( '[]', 0, 'valid empty findings array' ),
 array( "FILE: sample.php\n[]\n", 0, 'file-scoped empty findings array' ),
 array( '', 1, 'empty report' ),
 array( 'unexpected output', 1, 'malformed report' ),
);
$failures = 0;
try {
 foreach ( $cases as $case ) {
  file_put_contents( $report, $case[0] );
  $command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( __DIR__ ) . '/bin/ci-plugin-check.php' ) . ' ' . escapeshellarg( $report ) . ' inline-order-editor-pro';
  $pipes = array(); $process = proc_open( $command, array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
  if ( ! is_resource( $process ) ) { throw new RuntimeException( 'Could not start gate.' ); }
  stream_get_contents( $pipes[1] ); stream_get_contents( $pipes[2] ); fclose( $pipes[1] ); fclose( $pipes[2] );
  $status = proc_close( $process ); $passed = 0 === $case[1] ? 0 === $status : 0 !== $status;
  echo ( $passed ? 'PASS ' : 'FAIL ' ) . $case[2] . "\n";
  if ( ! $passed ) { ++$failures; }
 }
} finally { unlink( $report ); }
exit( $failures ? 1 : 0 );
