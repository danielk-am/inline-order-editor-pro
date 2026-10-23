<?php
/**
 * Add-on checks, run inside a WordPress admin request on a disposable site, with WooCommerce,
 * Inline Order Editor and this add-on active. Plain assertions. Not shipped in the release ZIP.
 *
 * Everything made here is removed again at the end, and every setting changed is put back.
 *
 * @package Inline_Order_Editor_Pro
 */

defined( 'ABSPATH' ) || exit;

$ioefwp_results = array();
$ioefwp_made    = array(
	'orders'   => array(),
	'products' => array(),
	'rates'    => array(),
	'coupons'  => array(),
);
$ioefwp_options = array();

$ioefwp_check = static function ( string $label, bool $passed ) use ( &$ioefwp_results ): void {
	$ioefwp_results[] = array( $label, $passed );
};

$ioefwp_group = static function ( string $name, callable $tests ) use ( $ioefwp_check ): void {
	try {
		$tests( $ioefwp_check );
	} catch ( Throwable $error ) {
		$ioefwp_check( $name . ': ' . get_class( $error ) . ' - ' . $error->getMessage() . ' (' . basename( $error->getFile() ) . ':' . $error->getLine() . ')', false );
	}
};

$ioefwp_option = static function ( string $name, $value ) use ( &$ioefwp_options ): void {
	if ( ! array_key_exists( $name, $ioefwp_options ) ) {
		$ioefwp_options[ $name ] = get_option( $name, null );
	}
	update_option( $name, $value );
};

$ioefwp_order = static function ( string $status = 'pending' ) use ( &$ioefwp_made ): WC_Order {
	$order = wc_create_order( array( 'status' => $status ) );
	$order->set_billing_first_name( 'Test' );
	$order->set_billing_last_name( 'Customer' );
	$order->set_billing_country( WC()->countries->get_base_country() );
	$order->set_billing_email( 'ioefwp-test@example.com' );
	$order->save();
	$ioefwp_made['orders'][] = $order->get_id();

	return $order;
};

$ioefwp_product = static function ( string $price ) use ( &$ioefwp_made ): WC_Product {
	$product = new WC_Product_Simple();
	$product->set_name( 'IOEFWP test product ' . wp_generate_password( 6, false ) );
	$product->set_regular_price( $price );
	$product->set_status( 'publish' );
	$product->save();
	$ioefwp_made['products'][] = $product->get_id();

	return $product;
};

$ioefwp_near = static function ( $actual, $expected ): bool {
	return abs( (float) $actual - (float) $expected ) < 0.005;
};

$ioefwp_fresh = static function ( WC_Order $order ): WC_Order {
	return wc_get_order( $order->get_id() );
};

$ioefwp_notes = static function ( WC_Order $order ): string {
	return implode( "\n", wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ) );
};

$ioefwp_output = static function ( callable $callback ): string {
	ob_start();
	$callback();

	return (string) ob_get_clean();
};

$ioefwp_option( 'woocommerce_calc_taxes', 'no' );
$ioefwp_option( 'woocommerce_prices_include_tax', 'no' );
$ioefwp_option( 'woocommerce_tax_round_at_subtotal', 'no' );
$ioefwp_option( 'woocommerce_tax_based_on', 'billing' );

$ioefwp_group(
	'Environment',
	static function ( callable $check ): void {
		$check( 'Inline Order Editor is active, and the add-on started from it.', class_exists( 'IOEFW_Orders' ) && did_action( 'ioefw_loaded' ) > 0 && false !== has_action( 'wp_ajax_ioefwp_add_discount' ) );
		$check( 'The add-on declares itself compatible with HPOS.', in_array( plugin_basename( IOEFWP_PLUGIN_FILE ), \Automattic\WooCommerce\Utilities\FeaturesUtil::get_compatible_plugins_for_feature( 'custom_order_tables' )['compatible'], true ) );
		$check( 'Its request is for logged-in users only.', false === has_action( 'wp_ajax_nopriv_ioefwp_add_discount' ) );
		$check( 'Emails stay inside the test store.', false !== has_filter( 'pre_wp_mail' ) );
	}
);

$ioefwp_group(
	'Saved terms',
	static function ( callable $check ) use ( $ioefwp_option, $ioefwp_output ): void {
		$posted = array(
			'keep' => '1',
			0      => array(
				'name' => 'Wedding <b>orders</b>',
				'text' => "50% deposit on order.\nBalance 7 days before the wedding.",
			),
			1      => array(
				'name' => '',
				'text' => 'A text with no name',
			),
			2      => array(
				'name' => 'A name with no text',
				'text' => '   ',
			),
			3      => array(
				'name' => 'Trade & "account" customers',
				'text' => 'Pay within 30 days.',
			),
		);
		$clean  = IOEFWP_Saved_Terms::sanitize_field( 'flattened by WooCommerce', array(), $posted );

		$check( 'Rows without a name or a text are dropped.', 2 === count( $clean ) );
		$check( 'Names are plain text, and the terms keep their line breaks.', 'Wedding orders' === $clean[0]['name'] && "50% deposit on order.\nBalance 7 days before the wedding." === $clean[0]['text'] );
		$check( 'An empty list is saved as empty, so every row can be removed.', array() === IOEFWP_Saved_Terms::sanitize_field( null, array(), array( 'keep' => '1' ) ) );

		$ioefwp_option( IOEFWP_Saved_Terms::OPTION, $clean );
		$check( 'The saved list is read back the same.', $clean === IOEFWP_Saved_Terms::get_all() );

		$picker = $ioefwp_output(
			static function () {
				do_action( 'ioefw_terms_box_actions', null );
			}
		);
		$check( 'The order\'s terms box offers each saved terms by name.', false !== strpos( $picker, 'ioefwp-saved-terms-picker' ) && false !== strpos( $picker, '>Wedding orders</option>' ) && false !== strpos( $picker, 'Trade &amp; &quot;account&quot; customers' ) );
		$check( 'The text is carried in the option, escaped.', false !== strpos( $picker, 'value="Pay within 30 days."' ) );

		$settings = apply_filters( 'woocommerce_general_settings', array() );
		$ids      = wp_list_pluck( $settings, 'id' );
		$check( 'The Saved terms section follows the Order terms settings.', in_array( IOEFWP_Saved_Terms::OPTION, $ids, true ) && array_search( 'ioefw_default_terms', $ids, true ) < array_search( IOEFWP_Saved_Terms::OPTION, $ids, true ) );

		$field = $ioefwp_output(
			static function () {
				IOEFWP_Saved_Terms::render_field( array( 'title' => 'Saved terms' ) );
			}
		);
		$check( 'The settings field shows each saved terms and one empty row.', 3 === substr_count( $field, 'ioefwp-saved-terms__row"' ) && false !== strpos( $field, 'name="ioefwp_saved_terms[0][name]" value="Wedding orders"' ) && false !== strpos( $field, 'name="ioefwp_saved_terms[keep]"' ) );

		$ioefwp_option( IOEFWP_Saved_Terms::OPTION, array() );
		$check(
			'With nothing saved, the picker is not shown.',
			'' === trim(
				$ioefwp_output(
					static function () {
						IOEFWP_Saved_Terms::render_picker( null );
					}
				)
			)
		);
	}
);

$ioefwp_group(
	'Order discount',
	static function ( callable $check ) use ( $ioefwp_order, $ioefwp_product, $ioefwp_option, $ioefwp_near, $ioefwp_fresh, $ioefwp_notes, &$ioefwp_made ): void {
		$product = $ioefwp_product( '80' );
		$order   = $ioefwp_order();
		$line_id = $order->add_product( $product, 1 );
		$order->calculate_totals();
		$order = IOEFW_Orders::add_custom_item( $ioefwp_fresh( $order ), array( 'name' => 'Gift wrap', 'price' => '20' ) );
		$check( 'The order starts at 100.', $ioefwp_near( $order->get_total(), 100 ) );

		// Where the screen gets its button and its form: the button among the order's buttons, the form under the totals.
		$drawn = static function ( WC_Order $shown ): array {
			ob_start();
			do_action( 'woocommerce_order_item_add_action_buttons', $shown );
			$buttons = (string) ob_get_clean();
			ob_start();
			do_action( 'woocommerce_admin_order_totals_after_total', $shown->get_id() );

			return array( $buttons, (string) ob_get_clean() );
		};
		list( $buttons, $totals ) = $drawn( $order );
		$check( 'The Add discount button is among the order\'s buttons, and the form is not.', false !== strpos( $buttons, 'ioefwp-discount-open' ) && false === strpos( $buttons, 'ioefwp-discount-form' ) );
		$check( 'The form is a hidden row of its own under the totals.', 1 === preg_match( '/<tr class="ioefwp-discount-row" hidden>.*ioefwp-discount-amount.*ioefwp-discount-label.*ioefwp-discount-apply.*ioefwp-discount-cancel.*<\/tr>/s', $totals ) );

		$order = IOEFWP_Discount::add( $order, '10', 'Loyal customer' );
		$fees  = $order instanceof WC_Order ? array_values( $order->get_fees() ) : array();
		$check( 'A discount of 10 is a fee line below zero, named as typed.', 1 === count( $fees ) && 'Loyal customer' === $fees[0]->get_name() && $ioefwp_near( $fees[0]->get_total(), -10 ) );
		$check( 'The order total drops to 90.', $ioefwp_near( $order->get_total(), 90 ) );
		$check( 'An order note records the discount.', false !== strpos( $ioefwp_notes( $order ), 'Added discount: Loyal customer' ) );

		$order = IOEFWP_Discount::add( $order, '10%' );
		$check( 'A percentage is taken from the items: 10% of 100 is 10 more off.', $order instanceof WC_Order && $ioefwp_near( $order->get_total(), 80 ) && 2 === count( $order->get_fees() ) );
		$check( 'Its note says the percentage was worked out once.', false !== strpos( $ioefwp_notes( $order ), 'Worked out once as 10% of the items' ) );

		$check( 'Zero is refused.', is_wp_error( IOEFWP_Discount::add( $order, '0' ) ) );
		$check( 'A negative amount is refused.', is_wp_error( IOEFWP_Discount::add( $order, '-5' ) ) );
		$check( 'Something that is not a number is refused.', is_wp_error( IOEFWP_Discount::add( $order, 'ten' ) ) );
		$check( 'More than the items on the order is refused.', is_wp_error( IOEFWP_Discount::add( $order, '150' ) ) );
		$check( 'More than 100% is refused.', is_wp_error( IOEFWP_Discount::add( $order, '120%' ) ) );
		$check( 'The refused discounts changed nothing.', $ioefwp_near( $ioefwp_fresh( $order )->get_total(), 80 ) && 2 === count( $ioefwp_fresh( $order )->get_fees() ) );

		// The discount stays when a line is changed and when coupons are applied again.
		$coupon = new WC_Coupon();
		$coupon->set_code( 'ioefwp-cart-' . strtolower( wp_generate_password( 6, false ) ) );
		$coupon->set_discount_type( 'fixed_cart' );
		$coupon->set_amount( 5 );
		$coupon->save();
		$ioefwp_made['coupons'][] = $coupon->get_id();
		$order = $ioefwp_fresh( $order );
		$order->apply_coupon( $coupon->get_code() );
		$order = IOEFW_Orders::update_item( $ioefwp_fresh( $order ), $line_id, 'quantity', '2' );
		$check( 'Both discounts survive a coupon and a changed line: 180 less 5, less 20.', $order instanceof WC_Order && 2 === count( $order->get_fees() ) && $ioefwp_near( $order->get_total(), 155 ) );

		$paid = $ioefwp_fresh( $order );
		$paid->set_status( 'processing' );
		$paid->save();
		$check( 'A paid order cannot be discounted.', is_wp_error( IOEFWP_Discount::add( $ioefwp_fresh( $paid ), '5' ) ) );
		// Other plugins print on the same hooks, so look for the add-on's own markup only.
		$check( 'A paid order is offered neither the button nor the form.', false === strpos( implode( '', $drawn( $ioefwp_fresh( $paid ) ) ), 'ioefwp-discount' ) );

		// Tax added on top.
		$ioefwp_option( 'woocommerce_calc_taxes', 'yes' );
		$rate_id                = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => '',
				'tax_rate_state'    => '',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'Test tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);
		$ioefwp_made['rates'][] = $rate_id;

		$taxed = IOEFW_Orders::add_custom_item( $ioefwp_order(), array( 'name' => 'Taxed item', 'price' => '100' ) );
		$check( 'With 10% tax on top, 100 is 110.', $ioefwp_near( $taxed->get_total(), 110 ) );
		$taxed = IOEFWP_Discount::add( $taxed, '10' );
		$check( 'A discount of 10 takes its tax with it: 90 plus 9 is 99.', $taxed instanceof WC_Order && $ioefwp_near( $taxed->get_total(), 99 ) );

		// Prices entered with tax.
		$ioefwp_option( 'woocommerce_prices_include_tax', 'yes' );
		$inclusive = IOEFW_Orders::add_custom_item( $ioefwp_order(), array( 'name' => 'Item with tax', 'price' => '110' ) );
		$check( 'On a store with tax in its prices, 110 typed is 110 paid.', $ioefwp_near( $inclusive->get_total(), 110 ) );
		$inclusive = IOEFWP_Discount::add( $inclusive, '11' );
		$check( 'A discount of 11 typed is 11 off what the customer pays.', $inclusive instanceof WC_Order && $ioefwp_near( $inclusive->get_total(), 99 ) );
		$inclusive = IOEFWP_Discount::add( $inclusive, '10%' );
		$check( 'A percentage is taken from the items with tax: 10% of 110 is 11 more off.', $inclusive instanceof WC_Order && $ioefwp_near( $inclusive->get_total(), 88 ) );

		$ioefwp_option( 'woocommerce_calc_taxes', 'no' );
		$ioefwp_option( 'woocommerce_prices_include_tax', 'no' );
	}
);

$ioefwp_group(
	'Repeat order',
	static function ( callable $check ) use ( $ioefwp_order, $ioefwp_product, $ioefwp_near, $ioefwp_fresh, $ioefwp_notes, &$ioefwp_made ): void {
		$product = $ioefwp_product( '30' );
		$gone    = $ioefwp_product( '15' );
		$order   = $ioefwp_order();
		$order->set_shipping_first_name( 'Ship' );
		$order->set_shipping_city( 'Shiptown' );
		$order->add_product( $product, 2 );
		$order->add_product( $gone, 1 );
		$order->calculate_totals();
		$order = IOEFW_Orders::add_custom_item( $ioefwp_fresh( $order ), array( 'name' => 'Engraving', 'quantity' => '2', 'price' => '7.50', 'note' => 'Initials AB' ) );
		$order = IOEFW_Orders::add_delivery( $order, array( 'name' => 'Courier', 'amount' => '8' ) );
		$order = IOEFWP_Discount::add( $order, '5' );
		IOEFW_Terms::set( $order, 'Pay on collection.' );
		$order = $ioefwp_fresh( $order );
		$order->set_payment_method( 'bacs' );
		$order->set_transaction_id( 'TX-123' );
		$order->save();

		// Since the first order: the price went up, and one product was deleted.
		$product->set_regular_price( '35' );
		$product->save();
		wp_delete_post( $gone->get_id(), true );

		$new = IOEFWP_Order_Actions::repeat_order( $ioefwp_fresh( $order ) );
		$check( 'Repeating makes a new order.', $new instanceof WC_Order && $new->get_id() !== $order->get_id() );

		if ( ! $new instanceof WC_Order ) {
			return;
		}

		$ioefwp_made['orders'][] = $new->get_id();
		$names                   = array();
		$typed                   = null;

		foreach ( $new->get_items() as $item ) {
			$names[] = $item->get_name();
			if ( 'Engraving' === $item->get_name() ) {
				$typed = $item;
			}
		}

		$check( 'It is Pending payment, made in the admin, for the same customer details.', 'pending' === $new->get_status() && 'admin' === $new->get_created_via() && 'Test' === $new->get_billing_first_name() && 'Shiptown' === $new->get_shipping_city() );
		$check( 'The catalogue product is on it at today\'s price: 2 at 35.', in_array( $product->get_name(), $names, true ) && $ioefwp_near( $new->get_subtotal(), 70 + 15 ) );
		$check( 'The typed item is copied with its price and its note.', $typed && 0 === $typed->get_product_id() && 2 === (int) $typed->get_quantity() && $ioefwp_near( $typed->get_total(), 15 ) && 'Initials AB' === $typed->get_meta( 'Note' ) && 'yes' === $typed->get_meta( IOEFW_Orders::CUSTOM_ITEM_META ) );
		$check( 'The delivery charge is copied.', 1 === count( $new->get_items( 'shipping' ) ) && $ioefwp_near( $new->get_shipping_total(), 8 ) );
		$check( 'The deleted product is left out, and the note names it.', ! in_array( $gone->get_name(), $names, true ) && false !== strpos( $ioefwp_notes( $new ), $gone->get_name() ) );
		$check( 'No discount, fee, coupon or payment detail is copied.', 0 === count( $new->get_fees() ) && 0 === count( $new->get_items( 'coupon' ) ) && '' === $new->get_payment_method() && '' === $new->get_transaction_id() && ! $new->get_date_paid() );
		$check( 'The total is the lines and delivery: 70 plus 15 plus 8.', $ioefwp_near( $new->get_total(), 93 ) );
		$check( 'The terms are copied.', 'Pay on collection.' === IOEFW_Terms::get( $new ) );
		$check( 'Each order names the other.', (int) $new->get_meta( IOEFWP_Order_Actions::REPEATED_FROM_META ) === $order->get_id() && false !== strpos( $ioefwp_notes( $new ), 'Repeated from order ' . $order->get_order_number() ) && false !== strpos( $ioefwp_notes( $ioefwp_fresh( $order ) ), 'Repeated as new order ' . $new->get_order_number() ) );
		$check( 'The first order is left as it was.', $ioefwp_near( $ioefwp_fresh( $order )->get_total(), 60 + 15 + 15 + 8 - 5 ) && 'bacs' === $ioefwp_fresh( $order )->get_payment_method() );
	}
);

$ioefwp_group(
	'Mark as paid',
	static function ( callable $check ) use ( $ioefwp_order, $ioefwp_fresh, $ioefwp_notes ): void {
		$ways = IOEFWP_Order_Actions::payment_ways();
		$check( 'Cash is always offered.', isset( $ways['cash'] ) && 'ioefwp_cash' === $ways['cash']['id'] );

		$gateways = WC()->payment_gateways()->payment_gateways();
		$honest   = true;
		foreach ( $ways as $way => $payment ) {
			if ( 'cash' !== $way && ( ! isset( $gateways[ $way ] ) || 'yes' !== $gateways[ $way ]->enabled ) ) {
				$honest = false;
			}
		}
		$check( 'Every other way offered is a payment method the shop has switched on.', $honest );

		$order = IOEFW_Orders::add_custom_item( $ioefwp_order(), array( 'name' => 'Paid item', 'price' => '40' ) );
		$check( 'A pending order above zero can be marked as paid.', true === IOEFWP_Order_Actions::can_mark_paid( $order ) );
		$check( 'A way of paying that is not offered is refused.', is_wp_error( IOEFWP_Order_Actions::mark_paid( $order, 'bitcoin' ) ) && ! $ioefwp_fresh( $order )->get_date_paid() );

		delete_option( 'ioefw_test_mail' );
		$paid = IOEFWP_Order_Actions::mark_paid( $ioefwp_fresh( $order ), 'cash' );
		$check( 'Marking as paid records the date, the way and a paid status.', $paid instanceof WC_Order && $paid->get_date_paid() && 'ioefwp_cash' === $paid->get_payment_method() && 'Cash' === $paid->get_payment_method_title() && $paid->has_status( array( 'processing', 'completed' ) ) );
		$check( 'An order note says how it was paid and who recorded it.', false !== strpos( $ioefwp_notes( $paid ), 'Marked as paid:' ) && false !== strpos( $ioefwp_notes( $paid ), 'by Cash' ) && false !== strpos( $ioefwp_notes( $paid ), wp_get_current_user()->display_name ) );
		$check( 'WooCommerce sent its paid-order emails, caught by the test store.', count( (array) get_option( 'ioefw_test_mail', array() ) ) >= 1 );

		$again = IOEFWP_Order_Actions::mark_paid( $ioefwp_fresh( $order ), 'cash' );
		$check( 'It cannot be marked as paid twice.', is_wp_error( $again ) );

		$free = IOEFW_Orders::add_custom_item( $ioefwp_order(), array( 'name' => 'Free item', 'price' => '0' ) );
		$check( 'An order of zero is not waiting for payment.', is_wp_error( IOEFWP_Order_Actions::can_mark_paid( $free ) ) );

		$actions = apply_filters( 'woocommerce_order_actions', array(), $ioefwp_fresh( $order ) );
		$check( 'A paid order is no longer offered Mark as paid.', ! isset( $actions['ioefwp_paid_cash'] ) && isset( $actions['ioefwp_repeat_order'] ) );
	}
);

$ioefwp_group(
	'Typed items as draft products',
	static function ( callable $check ) use ( $ioefwp_order, $ioefwp_product, $ioefwp_near, $ioefwp_fresh, $ioefwp_notes, &$ioefwp_made ): void {
		$product = $ioefwp_product( '12' );
		$order   = $ioefwp_order();
		$order->add_product( $product, 1 );
		$order->calculate_totals();
		$order = IOEFW_Orders::add_custom_item( $ioefwp_fresh( $order ), array( 'name' => 'Hand-painted card', 'quantity' => '3', 'price' => '4.50' ) );
		$order = IOEFW_Orders::add_custom_item( $order, array( 'name' => 'Ribbon by the metre', 'price' => '2' ) );

		$actions = apply_filters( 'woocommerce_order_actions', array(), $order );
		$check( 'An order with typed items offers all three kinds of action.', isset( $actions['ioefwp_repeat_order'], $actions['ioefwp_paid_cash'], $actions['ioefwp_save_drafts'] ) );

		$new = new WC_Order();
		$new->set_status( 'auto-draft' );
		$check( 'An order that is not created yet offers none.', array() === apply_filters( 'woocommerce_order_actions', array(), $new ) );

		$made = IOEFWP_Order_Actions::save_typed_items_as_drafts( $order );
		$check( 'Each typed item becomes one product.', is_array( $made ) && 2 === count( $made ) );

		if ( ! is_array( $made ) ) {
			return;
		}

		$ioefwp_made['products'] = array_merge( $ioefwp_made['products'], $made );
		$card                    = wc_get_product( $made[0] );

		$check( 'The product is a draft, with the item\'s name and price each.', $card && 'draft' === $card->get_status() && 'Hand-painted card' === $card->get_name() && $ioefwp_near( $card->get_regular_price(), 4.5 ) );
		$check( 'Nothing was published.', 'draft' === get_post_status( $made[0] ) && 'draft' === get_post_status( $made[1] ) );

		$order = $ioefwp_fresh( $order );
		$lines = array_values( $order->get_items() );
		$check( 'The order\'s lines are as they were: typed, with no product behind them.', 3 === count( $lines ) && 0 === $lines[1]->get_product_id() && $ioefwp_near( $order->get_total(), 12 + 13.5 + 2 ) );
		$check( 'Each line remembers its draft, in a hidden detail.', (int) $lines[1]->get_meta( IOEFWP_Order_Actions::PRODUCT_META ) === $made[0] && in_array( IOEFWP_Order_Actions::PRODUCT_META, apply_filters( 'woocommerce_hidden_order_itemmeta', array() ), true ) );
		$check( 'An order note lists the drafts.', false !== strpos( $ioefwp_notes( $order ), 'Saved as draft products, not published: Hand-painted card (#' . $made[0] . ')' ) );
		$check( 'Running it again makes nothing new.', array() === IOEFWP_Order_Actions::save_typed_items_as_drafts( $order ) );
		$check( 'The action is no longer offered.', ! isset( apply_filters( 'woocommerce_order_actions', array(), $order )['ioefwp_save_drafts'] ) );
	}
);

$ioefwp_group(
	'Uninstall',
	static function ( callable $check ): void {
		$uninstall = (string) file_get_contents( dirname( IOEFWP_PLUGIN_FILE ) . '/uninstall.php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$check( 'uninstall.php removes the add-on\'s one setting.', false !== strpos( $uninstall, "'" . IOEFWP_Saved_Terms::OPTION . "'" ) );
	}
);

// Integration checks protect real WooCommerce data rather than mirrored test getters.
$ioefwp_group(
 'Local document exports',
 static function ( callable $check ) use ( $ioefwp_order, &$ioefwp_made ): void {
  $check( 'Local renderer and block templates are loaded.', Alkamy_Local_Pdf::available() && class_exists( 'Alkamy_Layout_Editor' ) );
  $order = $ioefwp_order();$item = new WC_Order_Item_Product();$item->set_name( 'PDF test display' );$item->set_quantity( 2 );$item->set_subtotal( 100 );$item->set_total( 100 );$order->add_item( $item );
  $discount = new WC_Order_Item_Fee();$discount->set_name( 'Pro discount' );$discount->set_total( -10 );$order->add_item( $discount );$order->set_total( 90 );$order->save();
  $before = hash( 'sha256', wp_json_encode( wc_get_order( $order->get_id() )->get_data() ) );
  $http = 0;$spy = static function ( $value ) use ( &$http ) { ++$http;return new WP_Error( 'unexpected_http', 'Local PDF must not make network requests.' ); };add_filter( 'pre_http_request', $spy );
  try {
   $record = Alkamy_Admin::local_record( $order, array( 'type'=>'invoice', 'alkamy_address'=>array( '_billing_first_name'=>'Unsaved' ), 'sections'=>array( array( 'heading'=>'Draft terms', 'body'=>'Draft body', 'documents'=>array( 'invoice' ) ) ), 'alkamy_fields_present'=>'1', 'alkamy_custom_fields'=>array( array( 'label'=>'Draft reference', 'value'=>'PO-UAT' ) ) ) );
   $check( 'Real negative fee reconciles in local document.', 9000 === $record['snapshot']['total'] && -1000 === $record['snapshot']['lines'][1]['net'] );
   $check( 'Preview reflects unsaved address, sections and custom fields.', false !== strpos( $record['snapshot']['customer']['name'], 'Unsaved' ) && 'Draft terms' === $record['snapshot']['sections'][0]['heading'] && 'PO-UAT' === $record['snapshot']['customFields'][0]['value'] );
   $check( 'Real order generates a PDF locally.', 0 === strpos( Alkamy_Local_Pdf::render( $record ), '%PDF-' ) );
   foreach ( array( 'quotation', 'packing_slip', 'delivery_order' ) as $type ) { $record = Alkamy_Local_Pdf::record( $order, $type );$check( $type . ' generates a PDF locally.', 0 === strpos( Alkamy_Local_Pdf::render( $record ), '%PDF-' ) ); }
  } finally { remove_filter( 'pre_http_request', $spy ); }
  $check( 'Local documents make no provider or service requests.', 0 === $http );
  $check( 'Draft PDF requests leave persisted order untouched.', $before === hash( 'sha256', wp_json_encode( wc_get_order( $order->get_id() )->get_data() ) ) );
  $order->set_status( 'completed' );$order->save();$record = Alkamy_Local_Pdf::record( $order, 'invoice' );$html = Alkamy_Layout::html( $record );
  $check( 'Paid export suppresses a fresh payment demand.', '' === $record['paymentUrl'] && false === strpos( $html, 'Payment due within' ) );
  $refund = wc_create_refund( array( 'order_id'=>$order->get_id(), 'amount'=>25, 'reason'=>'Test partial refund', 'refund_payment'=>false, 'restock_items'=>false ) );
  if ( is_wp_error( $refund ) ) { throw new RuntimeException( $refund->get_error_message() ); }
  $ioefwp_made['orders'][] = $refund->get_id();$order = wc_get_order( $order->get_id() );$record = Alkamy_Local_Pdf::record( $order, 'credit_note', $refund->get_id() );
  $check( 'Existing partial refund uses its own credit amount and order reference.', 2500 === $record['snapshot']['total'] && 'Unnumbered invoice' === $record['originalNumber'] );
  $check( 'Existing refund credit note generates a PDF locally.', 0 === strpos( Alkamy_Local_Pdf::render( $record ), '%PDF-' ) );
  $request = new WP_REST_Request( 'GET', '/wp/v2/alkamy_pdf_layout' );$user = get_current_user_id();wp_set_current_user( 0 );
  try { $denied = Alkamy_Layout_Editor::rest_access( null, rest_get_server(), $request );$check( 'Anonymous REST cannot read store layouts.', is_wp_error( $denied ) && 403 === $denied->get_error_data()['status'] ); } finally { wp_set_current_user( $user ); }
 }
);

$ioefwp_group('Assigned document numbers',static function(callable $check) use($ioefwp_order,&$ioefwp_made):void{
 $order=$ioefwp_order();$order->set_total(0);$order->save();$other=$ioefwp_order();$other->save();
 $input=array('invoice'=>'INV-UAT-0001','quotation'=>'QUOT-UAT-0001','delivery_order'=>'DO-UAT-0001','packing_slip'=>'PS-UAT-0001');
 Alkamy_Document_Numbers::apply($order,$input);$order->save();$order=wc_get_order($order->get_id());
 foreach($input as $type=>$number){$record=Alkamy_Local_Pdf::record($order,$type);$check('Saved '.$type.' reference retained.', $number === $record['number']);$html=Alkamy_Layout::html($record);$check('PDF masthead separates '.$type.' number and Woo reference.',false !== strpos($html,$number) && (string)$order->get_order_number() === $record['orderReference']);}
 try{Alkamy_Document_Numbers::apply($other,array('invoice'=>'INV-UAT-0001'));$check('Duplicate invoice number refused.',false);}catch(InvalidArgumentException $e){$check('Duplicate invoice number refused.',true);}
 $hash=hash('sha256',wp_json_encode(wc_get_order($order->get_id())->get_data()));
 $record=Alkamy_Admin::local_record($order,array('type'=>'invoice','alkamy_document_numbers'=>array('invoice'=>'INV-UAT-DRAFT')));
 $check('Unsaved number is visible in draft preview.','INV-UAT-DRAFT' === $record['number']);
 $check('Number preview leaves persisted order untouched.',$hash === hash('sha256',wp_json_encode(wc_get_order($order->get_id())->get_data())));
 $check('WooCommerce order number remains unchanged.',(string)$order->get_id() === (string)$order->get_order_number());
});

// Optional browser fixture for the disposable store only; tests are excluded from shipped packages.
if(isset($_GET['number_fixture'])){
 $fixture_id=(int)get_option('alkamy_number_fixture');$fixture=$fixture_id ? wc_get_order($fixture_id) : false;
 if(!$fixture){$fixture=wc_create_order();$fixture->set_currency('SGD');$fixture->set_billing_first_name('Sample');$fixture->set_billing_last_name('Buyer');$fixture->set_billing_email('buyer@example.test');$item=new WC_Order_Item_Product();$item->set_name('Sample display');$item->set_quantity(2);$item->set_subtotal(100);$item->set_total(100);$fixture->add_item($item);$fixture->set_total(100);$fixture->save();update_option('alkamy_number_fixture',$fixture->get_id(),false);}
 echo 'Number fixture: ' . esc_url($fixture->get_edit_order_url()) . "\n";
}

// Clean up, and put the settings back.
foreach ( array_unique( $ioefwp_made['orders'] ) as $ioefwp_id ) {
	$ioefwp_cleanup = wc_get_order( $ioefwp_id );
	if ( $ioefwp_cleanup ) {
		$ioefwp_cleanup->delete( true );
	}
}
foreach ( array_unique( $ioefwp_made['products'] ) as $ioefwp_id ) {
	wp_delete_post( $ioefwp_id, true );
}
foreach ( $ioefwp_made['coupons'] as $ioefwp_id ) {
	wp_delete_post( $ioefwp_id, true );
}
foreach ( $ioefwp_made['rates'] as $ioefwp_id ) {
	WC_Tax::_delete_tax_rate( $ioefwp_id );
}
foreach ( $ioefwp_options as $ioefwp_name => $ioefwp_value ) {
	if ( null === $ioefwp_value ) {
		delete_option( $ioefwp_name );
	} else {
		update_option( $ioefwp_name, $ioefwp_value );
	}
}

$ioefwp_failed = 0;

foreach ( $ioefwp_results as $ioefwp_result ) {
	echo ( $ioefwp_result[1] ? 'PASS  ' : 'FAIL  ' ) . wp_strip_all_tags( $ioefwp_result[0] ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- A text/plain report on a test site.
	$ioefwp_failed += $ioefwp_result[1] ? 0 : 1;
}

echo "\n" . count( $ioefwp_results ) . ' checks, ' . (int) $ioefwp_failed . " failed.\n";
echo 'WordPress ' . esc_html( get_bloginfo( 'version' ) ) . ', WooCommerce ' . esc_html( WC()->version ) . ', PHP ' . esc_html( PHP_VERSION ) . ', Inline Order Editor ' . esc_html( IOEFW_VERSION ) . ', Pro ' . esc_html( IOEFWP_VERSION ) . "\n";
