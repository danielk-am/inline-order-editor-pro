<?php
/** Signed, server-to-server transport. No Stripe platform secrets reside in WordPress. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Service {
 public static function configuration() {
  $saved = get_option( 'alkamy_service_connection', array() );
  return array( 'url'=>defined( 'ALKAMY_SERVICE_URL' ) ? ALKAMY_SERVICE_URL : ( $saved['url'] ?? '' ), 'id'=>defined( 'ALKAMY_STORE_ID' ) ? ALKAMY_STORE_ID : ( $saved['id'] ?? '' ), 'secret'=>defined( 'ALKAMY_STORE_SECRET' ) ? ALKAMY_STORE_SECRET : ( $saved['secret'] ?? '' ) );
 }
 public static function configured() { $c = self::configuration();return $c['url'] && $c['id'] && strlen( $c['secret'] ) >= 32; }
 private static function validate_url( $url ) {
  $parts = wp_parse_url( $url );
  if ( ! is_array( $parts ) || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) || ! in_array( $parts['path'] ?? '', array( '', '/' ), true ) ) { throw new RuntimeException( 'Enter the service origin, without a path or login information.' ); }
  if ( 'https' !== ( $parts['scheme'] ?? '' ) && ! ( defined( 'ALKAMY_ALLOW_LOCAL_HTTP' ) && ALKAMY_ALLOW_LOCAL_HTTP && in_array( $parts['host'], array( '127.0.0.1', 'localhost' ), true ) && 'http' === ( $parts['scheme'] ?? '' ) ) ) { throw new RuntimeException( __( 'The service must use HTTPS.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  return rtrim( $url, '/' );
 }
 public static function connection_origin() {
  $url = defined( 'ALKAMY_SERVICE_URL' ) ? ALKAMY_SERVICE_URL : apply_filters( 'alkamy_service_origin', '' );
  return $url ? self::validate_url( $url ) : '';
 }
 private static function public_request( $url, $path, $input ) {
  $response = wp_remote_post( $url . $path, array( 'timeout'=>30, 'redirection'=>0, 'headers'=>array( 'Content-Type'=>'application/json' ), 'body'=>wp_json_encode( $input ), 'limit_response_size'=>10000 ) );
  if ( is_wp_error( $response ) ) { throw new RuntimeException( 'The connection service could not be reached. Retry connection.' ); }
  $data = json_decode( wp_remote_retrieve_body( $response ), true );
  if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) ) { throw new RuntimeException( 'Stripe connection could not be completed. Please retry or contact the service administrator.' ); }
  return $data;
 }
 public static function start_connection() {
  if ( ! current_user_can( 'manage_options' ) ) { throw new RuntimeException( 'Only a store administrator can connect Stripe.' ); }
  if ( defined( 'ALKAMY_STORE_ID' ) || defined( 'ALKAMY_STORE_SECRET' ) ) { throw new RuntimeException( 'This store connection is managed by server configuration.' ); }
  $url = self::connection_origin();
  if ( ! $url ) { throw new RuntimeException( 'Hosted connection is not available yet. Alkamy must configure its connection service first.' ); }
  $state = bin2hex( random_bytes( 32 ) );$verifier = bin2hex( random_bytes( 32 ) );
  $return_url = add_query_arg( array( 'action'=>'alkamy_connect_return', '_wpnonce'=>wp_create_nonce( 'alkamy_connect_return' ) ), admin_url( 'admin-post.php' ) );
  $result = self::public_request( $url, '/v1/connect/start', array( 'returnUrl'=>$return_url, 'state'=>$state, 'challenge'=>hash( 'sha256', $verifier ) ) );
  $redirect = $result['url'] ?? ''; $parts = wp_parse_url( $redirect );
  if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || 'connect.stripe.com' !== ( $parts['host'] ?? '' ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) ) { throw new RuntimeException( 'Invalid Stripe authorization URL.' ); }
  set_transient( 'alkamy_connect_' . get_current_user_id(), array( 'state'=>$state, 'verifier'=>$verifier, 'url'=>$url ), 15 * MINUTE_IN_SECONDS );
  return $redirect;
 }
 public static function finish_connection( $state, $code ) {
  if ( ! current_user_can( 'manage_options' ) ) { throw new RuntimeException( 'Only a store administrator can connect Stripe.' ); }
  $key = 'alkamy_connect_' . get_current_user_id();$pending = get_transient( $key );
  if ( ! is_array( $pending ) || ! is_string( $state ) || ! hash_equals( $pending['state'], $state ) ) { throw new RuntimeException( 'Connection expired or belongs to another session. Start again.' ); }
  if ( ! is_string( $code ) || ! preg_match( '/^[a-zA-Z0-9_-]{43,128}$/D', $code ) ) { throw new RuntimeException( 'Stripe authorization was cancelled or incomplete. Start again.' ); }
  // Keep pending state until saved so a lost exchange response can be retried.
  $data = self::public_request( self::validate_url( $pending['url'] ), '/v1/connect/exchange', array( 'code'=>$code, 'verifier'=>$pending['verifier'] ) );
  if ( ! preg_match( '/^[a-z0-9_-]{2,60}$/D', $data['storeId'] ?? '' ) || ! is_string( $data['storeSecret'] ?? null ) || strlen( $data['storeSecret'] ) < 32 || strlen( $data['storeSecret'] ) > 200 ) { throw new RuntimeException( 'Invalid store credentials received.' ); }
  $saved = array( 'url'=>$pending['url'], 'id'=>$data['storeId'], 'secret'=>$data['storeSecret'], 'connection_type'=>'oauth' );
  update_option( 'alkamy_service_connection', $saved, false );
  if ( get_option( 'alkamy_service_connection' ) !== $saved ) { throw new RuntimeException( 'Store credentials could not be saved. Retry this return page.' ); }
  delete_transient( $key );
 }
 public static function activate( $url, $code ) {
  if ( defined( 'ALKAMY_SERVICE_URL' ) || defined( 'ALKAMY_STORE_ID' ) || defined( 'ALKAMY_STORE_SECRET' ) ) { throw new RuntimeException( 'This connection is managed by server configuration. Remove those constants before using automatic setup.' ); }
  $url = self::validate_url( $url );
  if ( ! is_string( $code ) || strlen( $code ) < 32 || strlen( $code ) > 200 ) { throw new RuntimeException( 'Enter the complete connection code.' ); }
  $response = wp_remote_post( $url . '/v1/activate', array( 'timeout'=>30, 'redirection'=>0, 'headers'=>array( 'Content-Type'=>'application/json' ), 'body'=>wp_json_encode( array( 'code'=>$code ) ), 'limit_response_size'=>10000 ) );
  if ( is_wp_error( $response ) ) { throw new RuntimeException( 'Could not complete connection. If the code was consumed, request a fresh code.' ); }
  $data = json_decode( wp_remote_retrieve_body( $response ), true );
  if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! is_array( $data ) || ! preg_match( '/^[a-z0-9_-]{2,60}$/D', $data['storeId'] ?? '' ) || ! is_string( $data['storeSecret'] ?? null ) || strlen( $data['storeSecret'] ) < 32 || strlen( $data['storeSecret'] ) > 200 ) { throw new RuntimeException( 'Connection code was invalid, expired or already used. Request a fresh code.' ); }
  update_option( 'alkamy_service_connection', array( 'url'=>$url, 'id'=>$data['storeId'], 'secret'=>$data['storeSecret'] ), false );
 }
 public static function request( $method, $path, $data = null, $pdf = false ) {
  if ( ! self::configured() ) { throw new RuntimeException( __( 'Connect this store from the Invoicing setup screen first.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  $connection = self::configuration();$url = self::validate_url( $connection['url'] );
  $body = null === $data ? '' : wp_json_encode( $data );
  $time = (string) time(); $nonce = wp_generate_uuid4();
  $signature = hash_hmac( 'sha256', $time . "\n" . $nonce . "\n" . $method . "\n" . $path . "\n" . $body, $connection['secret'] );
  $response = wp_remote_request( $url . $path, array( 'method'=>$method, 'timeout'=>45, 'redirection'=>0, 'headers'=>array( 'Content-Type'=>'application/json', 'X-Alkamy-Store'=>$connection['id'], 'X-Alkamy-Time'=>$time, 'X-Alkamy-Nonce'=>$nonce, 'X-Alkamy-Signature'=>$signature ), 'body'=>$body, 'limit_response_size'=>10000000 ) );
  if ( is_wp_error( $response ) ) { throw new RuntimeException( __( 'The service could not be reached. Retry the same document action.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  $raw = wp_remote_retrieve_body( $response ); $result = json_decode( $raw, true ); $status = wp_remote_retrieve_response_code( $response );
  if ( $status < 200 || $status >= 300 ) { throw new RuntimeException( isset( $result['error'] ) ? sanitize_text_field( $result['error'] ) : __( 'The service rejected the request.', 'inline-order-editor-pro' ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception is plain data; admin catch escapes it at wp_die or stores it for esc_html notice display.
  if ( $pdf ) { if ( 0 !== strpos( $raw, '%PDF-' ) ) { throw new RuntimeException( 'Invalid PDF response.' ); } return $raw; }
  if ( ! is_array( $result ) ) { throw new RuntimeException( 'Invalid service response.' ); }
  return $result;
 }
}
