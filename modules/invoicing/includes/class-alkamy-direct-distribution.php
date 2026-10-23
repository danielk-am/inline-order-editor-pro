<?php
/** Optional setup panels for the direct distribution; omitted from the Marketplace package. */
defined( 'ABSPATH' ) || exit;
final class Alkamy_Direct_Distribution {
 public static function init() {
  add_action( 'admin_post_alkamy_dismiss_hitpay', array( __CLASS__, 'dismiss_hitpay' ) );
 }
 private static function form( $action, $order_id = 0, $doc = '' ) {
  echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="alkamy_action"><input type="hidden" name="operation" value="' . esc_attr( $action ) . '"><input type="hidden" name="order_id" value="' . esc_attr( $order_id ) . '"><input type="hidden" name="document_id" value="' . esc_attr( $doc ) . '">';
  wp_nonce_field( 'alkamy_action' );
 }
 public static function dismiss_hitpay() {
  if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Access denied.', 'inline-order-editor-pro' ), '', array( 'response'=>403 ) ); }
  check_admin_referer( 'alkamy_dismiss_hitpay' );
  update_user_meta( get_current_user_id(), '_alkamy_hitpay_dismissed', 1 );
  wp_safe_redirect( Alkamy_Admin::url() );exit;
 }
 private static function hitpay_recommendation() {
  if ( 'SG' !== WC()->countries->get_base_country() || get_user_meta( get_current_user_id(), '_alkamy_hitpay_dismissed', true ) ) { return; }
  $active = class_exists( 'WC_HitPay' );
  echo '<section class="alkamy-card"><h2>' . esc_html__( 'Accept PayNow and cards with HitPay', 'inline-order-editor-pro' ) . '</h2><p>' . esc_html__( 'Suggested because your store address is in Singapore. Merchant eligibility and available payment methods are confirmed during HitPay onboarding.', 'inline-order-editor-pro' ) . '</p><div class="alkamy-provider-actions">';
  $url = $active ? admin_url( 'admin.php?page=wc-settings&tab=checkout&section=hitpay' ) : 'https://dashboard.hit-pay.com/register?referral_code=DANIEXCBRX';
  echo '<a class="button button-primary" href="' . esc_url( $url ) . '"' . ( $active ? '' : ' target="_blank" rel="sponsored noopener"' ) . '>' . esc_html( $active ? __( 'Manage HitPay', 'inline-order-editor-pro' ) : __( 'Create a HitPay account ↗', 'inline-order-editor-pro' ) ) . '</a><a href="https://docs.hitpayapp.com/plugins/woocommerce" target="_blank" rel="noopener">' . esc_html__( 'HitPay setup guide ↗', 'inline-order-editor-pro' ) . '</a></div><p class="description">' . esc_html__( 'Alkamy may earn a commission if you sign up through our referral link and meet HitPay’s program terms.', 'inline-order-editor-pro' ) . '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="alkamy_dismiss_hitpay">';
  wp_nonce_field( 'alkamy_dismiss_hitpay' );
  echo '<button type="submit" class="button-link">' . esc_html__( 'Dismiss recommendation', 'inline-order-editor-pro' ) . '</button></form></section>';
 }
 public static function render() {
  self::hitpay_recommendation();
  echo '<section class="alkamy-card"><h2>' . esc_html__( 'Accept payments with WooPayments', 'inline-order-editor-pro' ) . '</h2><p>' . esc_html__( 'Use the official WooPayments extension to accept payments through WooCommerce. Availability and payment methods depend on your business country.', 'inline-order-editor-pro' ) . '</p>';
  $woopayments_active = class_exists( 'WC_Payments' );
  echo '<p>' . esc_html( $woopayments_active ? __( 'WooPayments is active. Complete setup and review available payment methods in WooCommerce payment settings.', 'inline-order-editor-pro' ) : __( 'Install or activate the official WooPayments extension, then complete its account setup.', 'inline-order-editor-pro' ) ) . '</p>';
  $woopayments_url = $woopayments_active ? admin_url( 'admin.php?page=wc-settings&tab=checkout' ) : 'https://automattic.pxf.io/c/7843923/1969108/22744';
  echo '<div class="alkamy-provider-actions"><a class="button button-primary" href="' . esc_url( $woopayments_url ) . '"' . ( $woopayments_active ? '' : ' target="_blank" rel="sponsored noopener"' ) . '>' . esc_html( $woopayments_active ? __( 'Open payment settings', 'inline-order-editor-pro' ) : __( 'Explore WooPayments ↗', 'inline-order-editor-pro' ) ) . '</a><a href="https://woocommerce.com/document/woopayments/startup-guide/" target="_blank" rel="noopener">' . esc_html__( 'WooPayments setup guide ↗', 'inline-order-editor-pro' ) . '</a></div><p class="description">' . esc_html__( 'Alkamy may earn a commission on eligible referrals through our WooPayments link. Referral eligibility is determined by the provider; this link does not connect your site to our agency account.', 'inline-order-editor-pro' ) . '</p>';
  echo '<details><summary>' . esc_html__( 'Optional setup with Alkamy', 'inline-order-editor-pro' ) . '</summary><p>' . esc_html__( 'Alkamy may earn revenue share when an eligible store is connected to our Automattic for Agencies account. Installing WooPayments alone does not establish this connection. Agency connection requires your approval; arrange it before your first WooPayments transaction. You can use WooPayments without connecting your store to Alkamy.', 'inline-order-editor-pro' ) . '</p><p><a href="https://automattic.com/for-agencies/program-incentives/" target="_blank" rel="noopener">' . esc_html__( 'Read eligibility terms ↗', 'inline-order-editor-pro' ) . '</a></p></details><p class="description">' . esc_html__( 'This panel provides setup guidance. Local invoice PDFs use the existing WooCommerce customer payment page when payment is due.', 'inline-order-editor-pro' ) . '</p></section>';
  echo '<details class="alkamy-card"><summary>' . esc_html__( 'Legacy hosted document service' , 'inline-order-editor-pro' ) . '</summary><p>' . esc_html__( 'Local PDFs do not require this service.', 'inline-order-editor-pro' ) . '</p><h2>' . esc_html__( 'Stripe Connect', 'inline-order-editor-pro' ) . '</h2>';
  if ( Alkamy_Service::configured() ) {
   try { $status = Alkamy_Service::request( 'GET', '/v1/status' ); echo '<p>' . esc_html( strtoupper( $status['mode'] ) . ' · ' . ( $status['ready'] ? __( 'Payments enabled', 'inline-order-editor-pro' ) : __( 'Onboarding required', 'inline-order-editor-pro' ) ) ) . '</p>'; foreach ( array( 'card_payments'=>'Cards', 'paynow_payments'=>'PayNow', 'grabpay_payments'=>'GrabPay' ) as $key=>$label ) { echo '<p>' . esc_html( $label . ': ' . ( $status['capabilities'][ $key ]['status'] ?? 'not requested' ) ) . '</p>'; } }
   catch ( Throwable $error ) { echo '<p>' . esc_html( $error->getMessage() ) . '</p>'; }
   $connection = get_option( 'alkamy_service_connection', array() );
   if ( 'oauth' !== ( $connection['connection_type'] ?? '' ) && 'oauth' !== ( $status['connectionType'] ?? '' ) ) { self::form( 'connect' ); echo '<label>' . esc_html__( 'Merchant email', 'inline-order-editor-pro' ) . '<input type="email" required name="email" value="' . esc_attr( get_option( 'admin_email' ) ) . '"></label><button class="button button-primary">' . esc_html__( 'Continue Stripe onboarding', 'inline-order-editor-pro' ) . '</button></form>'; }
   else { echo '<p>' . esc_html__( 'Your Stripe account is connected. Complete any outstanding requirements in your Stripe Dashboard.', 'inline-order-editor-pro' ) . '</p>'; }
  } else {
   echo '<p>' . esc_html__( 'Create a Stripe account or connect an existing account. Stripe will return you here and your store connection will be saved automatically.', 'inline-order-editor-pro' ) . '</p>';
   if ( current_user_can( 'manage_options' ) ) {
    $available = false;try { $available = (bool) Alkamy_Service::connection_origin(); } catch ( Throwable $error ) { echo '<p>' . esc_html( $error->getMessage() ) . '</p>'; }
    self::form( 'start_connection' );echo '<button class="button button-primary"' . ( $available ? '' : ' disabled' ) . '>' . esc_html__( 'Create or connect Stripe account', 'inline-order-editor-pro' ) . '</button></form>';
    if ( ! $available ) { echo '<p>' . esc_html__( 'Hosted connection is not available yet. Alkamy must configure its connection service before this button can be used.', 'inline-order-editor-pro' ) . '</p>'; }
   } else { echo '<p>' . esc_html__( 'Ask a store administrator to connect Stripe.', 'inline-order-editor-pro' ) . '</p>'; }
  }
  echo '</details>';
 }
}
