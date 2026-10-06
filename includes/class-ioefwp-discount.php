<?php
/**
 * Order discount: an amount or a percentage taken off the order, without a coupon.
 *
 * The discount is a fee line with an amount below zero. WooCommerce keeps such a line when coupons are
 * applied again, and prints it on emails and invoices as a line of its own.
 *
 * @package Inline_Order_Editor_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Add discount button, its request and the work behind it.
 */
final class IOEFWP_Discount {

	const NONCE = 'ioefwp-order';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'woocommerce_order_item_add_action_buttons', array( __CLASS__, 'render_button' ), 20 );
		add_action( 'wp_ajax_ioefwp_add_discount', array( __CLASS__, 'ajax_add' ) );
	}

	/**
	 * Adds a discount to an order.
	 *
	 * @param WC_Order $order    The order.
	 * @param string   $typed    The discount as typed: an amount such as "10", or a percentage such as "10%".
	 * @param string   $label    The name of the line. "Discount" when empty.
	 * @param array    $tax_args country, state, postcode and city to calculate tax for.
	 * @return WC_Order|WP_Error The order, read again after the change.
	 */
	public static function add( WC_Order $order, $typed, $label = '', array $tax_args = array() ) {
		$can_edit = IOEFW_Orders::can_edit( $order );

		if ( is_wp_error( $can_edit ) ) {
			return $can_edit;
		}

		$typed      = is_scalar( $typed ) ? trim( (string) $typed, " \n\r\t\v\x00" ) : '';
		$is_percent = '%' === substr( $typed, -1 );
		$number     = wc_format_decimal( $is_percent ? substr( $typed, 0, -1 ) : $typed );
		$inclusive  = IOEFW_Orders::amounts_include_tax( $order );
		$base       = self::items_subtotal( $order, $inclusive );
		$label      = sanitize_text_field( (string) $label );

		if ( '' === $label ) {
			$label = __( 'Discount', 'inline-order-editor-pro' );
		}

		if ( ! is_numeric( $number ) || (float) $number <= 0 ) {
			return new WP_Error( 'ioefwp_discount', __( 'Type a discount above zero, such as 10 or 10%.', 'inline-order-editor-pro' ) );
		}

		if ( $is_percent && (float) $number > 100 ) {
			return new WP_Error( 'ioefwp_discount', __( 'A discount cannot be more than 100%.', 'inline-order-editor-pro' ) );
		}

		$amount = $is_percent ? round( $base * (float) $number / 100, wc_get_price_decimals() ) : (float) $number;

		if ( $amount <= 0 || $amount > $base + 0.00001 ) {
			return new WP_Error( 'ioefwp_discount', __( 'The discount cannot be more than the items on the order.', 'inline-order-editor-pro' ) );
		}

		$fee = new WC_Order_Item_Fee();
		$fee->set_name( $label );
		$fee->set_tax_status( 'taxable' );
		$fee->set_amount( -$amount );
		$fee->set_total( -$amount );
		$order->add_item( $fee );
		$order->save();

		// On a store with tax-inclusive prices the typed discount includes tax, like every other typed amount.
		IOEFW_Orders::settle( $order, $inclusive ? array( $fee->get_id() => array( 'total' => -$amount ) ) : array(), $tax_args );

		if ( $is_percent ) {
			$note = sprintf(
				/* translators: 1: name of the discount, 2: amount, 3: percentage, 4: the items subtotal the percentage was taken from. */
				__( 'Added discount: %1$s, %2$s. Worked out once as %3$s%% of the items, which came to %4$s.', 'inline-order-editor-pro' ),
				$label,
				IOEFW_Orders::plain_price( $amount, $order ),
				$number,
				IOEFW_Orders::plain_price( $base, $order )
			);
		} else {
			/* translators: 1: name of the discount, 2: amount. */
			$note = sprintf( __( 'Added discount: %1$s, %2$s.', 'inline-order-editor-pro' ), $label, IOEFW_Orders::plain_price( $amount, $order ) );
		}

		$order->add_order_note( $note, false, true );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * The order's items before discounts, with tax when typed amounts include it.
	 *
	 * @param WC_Order $order     The order.
	 * @param bool     $inclusive Whether typed amounts include tax.
	 * @return float
	 */
	public static function items_subtotal( WC_Order $order, $inclusive ) {
		$subtotal = 0.0;

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$subtotal += (float) $item->get_subtotal() + ( $inclusive ? (float) $item->get_subtotal_tax() : 0 );
		}

		return $subtotal;
	}

	/**
	 * Draws the Add discount button and its small form in the Items box.
	 *
	 * @param mixed $order The order.
	 */
	public static function render_button( $order ) {
		if ( ! $order instanceof WC_Order || true !== IOEFW_Orders::can_edit( $order ) ) {
			return;
		}
		?>
		<button type="button" class="button ioefwp-discount-open"><?php esc_html_e( 'Add discount', 'inline-order-editor-pro' ); ?></button>
		<span class="ioefwp-discount-form" hidden>
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Discount: an amount or a percentage', 'inline-order-editor-pro' ); ?></span>
				<input type="text" class="ioefwp-discount-amount" inputmode="decimal" autocomplete="off" size="8" placeholder="<?php esc_attr_e( '10 or 10%', 'inline-order-editor-pro' ); ?>" />
			</label>
			<label>
				<span class="screen-reader-text"><?php esc_html_e( 'Name of the discount', 'inline-order-editor-pro' ); ?></span>
				<input type="text" class="ioefwp-discount-label" autocomplete="off" size="16" maxlength="100" placeholder="<?php esc_attr_e( 'Discount', 'inline-order-editor-pro' ); ?>" />
			</label>
			<button type="button" class="button button-primary ioefwp-discount-apply"><?php esc_html_e( 'Take it off', 'inline-order-editor-pro' ); ?></button>
			<button type="button" class="button-link ioefwp-discount-cancel"><?php esc_html_e( 'Cancel', 'inline-order-editor-pro' ); ?></button>
		</span>
		<?php
	}

	/**
	 * The Add discount request.
	 */
	public static function ajax_add() {
		check_ajax_referer( self::NONCE, 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to change orders.', 'inline-order-editor-pro' ) ), 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$typed    = isset( $_POST['amount'] ) ? sanitize_text_field( wp_unslash( $_POST['amount'] ) ) : '';
		$label    = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$tax_args = array();

		// The address in the form, read the way WooCommerce reads it for its own Recalculate button.
		if ( isset( $_POST['country'] ) ) {
			foreach ( array( 'country', 'state', 'postcode', 'city' ) as $key ) {
				$tax_args[ $key ] = isset( $_POST[ $key ] ) ? wc_strtoupper( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ) : '';
			}
		}

		$order = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order instanceof WC_Order ) {
			wp_send_json_error( array( 'message' => __( 'This order could not be found.', 'inline-order-editor-pro' ) ) );
		}

		$before = IOEFW_Orders::plain_price( $order->get_total(), $order );
		$result = self::add( $order, $typed, $label, $tax_args );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array_merge(
				IOEFW_Ajax::fragments( $result ),
				array(
					'total'       => IOEFW_Orders::plain_price( $result->get_total(), $result ),
					'totalBefore' => $before,
				)
			)
		);
	}
}
