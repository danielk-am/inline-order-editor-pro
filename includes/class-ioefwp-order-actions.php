<?php
/**
 * Three entries for the Order actions box: repeat an order, mark an order as paid, and save typed
 * items as draft products. Choosing an action and pressing Update is the confirmation.
 *
 * @package Inline_Order_Editor_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order actions.
 */
final class IOEFWP_Order_Actions {

	/**
	 * Hidden mark on a line whose typed item has been saved as a draft product.
	 */
	const PRODUCT_META = '_ioefwp_product_id';

	/**
	 * Order meta that names the order a repeat was made from.
	 */
	const REPEATED_FROM_META = '_ioefwp_repeated_from';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_order_actions', array( __CLASS__, 'add_actions' ), 20, 2 );
		add_action( 'woocommerce_order_action_ioefwp_repeat_order', array( __CLASS__, 'handle_repeat' ) );
		add_action( 'woocommerce_order_action_ioefwp_save_drafts', array( __CLASS__, 'handle_save_drafts' ) );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hide_product_meta' ) );

		// The shop's payment methods are not known this early, so every way is hooked and checked when it runs.
		foreach ( array( 'cash', 'cod', 'bacs', 'cheque' ) as $way ) {
			add_action(
				'woocommerce_order_action_ioefwp_paid_' . $way,
				static function ( $order ) use ( $way ) {
					self::handle_mark_paid( $order, $way );
				}
			);
		}
	}

	/**
	 * Adds the entries to the Order actions box.
	 *
	 * @param array $actions The actions on offer.
	 * @param mixed $order   The order, when WooCommerce passes it.
	 * @return array
	 */
	public static function add_actions( $actions, $order = null ) {
		global $theorder;

		$actions = (array) $actions;
		$order   = $order instanceof WC_Order ? $order : ( $theorder instanceof WC_Order ? $theorder : null );

		if ( ! $order || 'auto-draft' === $order->get_status() ) {
			return $actions;
		}

		$actions['ioefwp_repeat_order'] = __( 'Repeat this order as a new order', 'inline-order-editor-pro' );

		if ( true === self::can_mark_paid( $order ) ) {
			foreach ( self::payment_ways() as $way => $payment ) {
				/* translators: %s: how the order was paid, for example Cash. */
				$actions[ 'ioefwp_paid_' . $way ] = sprintf( __( 'Mark as paid: %s', 'inline-order-editor-pro' ), $payment['title'] );
			}
		}

		if ( current_user_can( 'edit_products' ) && self::typed_items_without_product( $order ) ) {
			$actions['ioefwp_save_drafts'] = __( 'Save typed items as draft products', 'inline-order-editor-pro' );
		}

		return $actions;
	}

	/**
	 * Keeps the draft product mark out of the item details on the order screen.
	 *
	 * @param array $keys Hidden item meta keys.
	 * @return array
	 */
	public static function hide_product_meta( $keys ) {
		$keys   = (array) $keys;
		$keys[] = self::PRODUCT_META;

		return $keys;
	}

	/* ---------- Repeat an order ---------- */

	/**
	 * Makes a new Pending payment order with the same customer and lines as an existing order.
	 *
	 * Catalogue products are added at today's prices. Typed items and delivery charges are copied as they
	 * were. No payment details, coupons, fees or discounts are copied.
	 *
	 * @param WC_Order $order The order to repeat.
	 * @return WC_Order|WP_Error The new order.
	 */
	public static function repeat_order( WC_Order $order ) {
		$new = wc_create_order(
			array(
				'status'      => 'pending',
				'customer_id' => $order->get_customer_id(),
				'created_via' => 'admin',
			)
		);

		if ( is_wp_error( $new ) ) {
			return $new;
		}

		$new->set_address( $order->get_address( 'billing' ), 'billing' );
		$new->set_address( $order->get_address( 'shipping' ), 'shipping' );
		$new->set_currency( $order->get_currency() );

		$skipped = array();

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( 'yes' === $item->get_meta( IOEFW_Orders::CUSTOM_ITEM_META ) ) {
				// A typed item: the same name, quantity and price, and the same details. A line that lost its
				// product, because the product was deleted, is not one of these and is left out below.
				$copy = new WC_Order_Item_Product();
				$copy->set_props(
					array(
						'name'      => $item->get_name(),
						'quantity'  => $item->get_quantity(),
						'tax_class' => $item->get_tax_class(),
						'subtotal'  => $item->get_subtotal(),
						'total'     => $item->get_subtotal(),
					)
				);
				$copy->add_meta_data( IOEFW_Orders::CUSTOM_ITEM_META, 'yes', true );

				foreach ( $item->get_meta_data() as $meta ) {
					if ( is_string( $meta->key ) && '' !== $meta->key && '_' !== $meta->key[0] && is_scalar( $meta->value ) ) {
						$copy->add_meta_data( $meta->key, $meta->value );
					}
				}

				$new->add_item( $copy );
			} elseif ( $product && $product->is_purchasable() ) {
				$new->add_product( $product, $item->get_quantity() );
			} else {
				$skipped[] = $item->get_name();
			}
		}

		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$copy = new WC_Order_Item_Shipping();
			$copy->set_props(
				array(
					'method_title' => $item->get_method_title(),
					'method_id'    => $item->get_method_id(),
					'total'        => $item->get_total(),
				)
			);
			$new->add_item( $copy );
		}

		$new->update_meta_data( self::REPEATED_FROM_META, $order->get_id() );
		$new->calculate_totals();
		$new->save();

		IOEFW_Terms::set( $new, IOEFW_Terms::get( $order ) );

		/* translators: %s: order number. */
		$note = sprintf( __( 'Repeated from order %s. Catalogue products are at today\'s prices. No payment details, coupons or discounts were copied.', 'inline-order-editor-pro' ), $order->get_order_number() );

		if ( $skipped ) {
			/* translators: %s: list of item names. */
			$note .= ' ' . sprintf( __( 'Left out because they can no longer be bought: %s.', 'inline-order-editor-pro' ), implode( ', ', $skipped ) );
		}

		$new->add_order_note( $note, false, true );
		/* translators: %s: order number. */
		$order->add_order_note( sprintf( __( 'Repeated as new order %s.', 'inline-order-editor-pro' ), $new->get_order_number() ), false, true );

		return wc_get_order( $new->get_id() );
	}

	/**
	 * Runs the repeat action, then sends the browser to the new order.
	 *
	 * @param mixed $order The order the action was chosen on.
	 */
	public static function handle_repeat( $order ) {
		if ( ! $order instanceof WC_Order || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$new = self::repeat_order( $order );

		if ( ! $new instanceof WC_Order ) {
			return;
		}

		$go_to_new = static function () use ( $new ) {
			return $new->get_edit_order_url();
		};

		// WooCommerce's orders screen, and the older post-based screen.
		add_filter( 'woocommerce_redirect_order_location', $go_to_new, 99 );
		add_filter( 'redirect_post_location', $go_to_new, 99 );
	}

	/* ---------- Mark as paid ---------- */

	/**
	 * The ways an order can be marked as paid: cash, plus the offline payment methods the shop has switched on.
	 *
	 * @return array[] Keyed by a short name, each with the payment method ID and title to record.
	 */
	public static function payment_ways() {
		$ways = array(
			'cash' => array(
				'id'    => 'ioefwp_cash',
				'title' => __( 'Cash', 'inline-order-editor-pro' ),
			),
		);

		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();

		foreach ( array( 'cod', 'bacs', 'cheque' ) as $id ) {
			if ( isset( $gateways[ $id ] ) && 'yes' === $gateways[ $id ]->enabled ) {
				$ways[ $id ] = array(
					'id'    => $id,
					'title' => wp_strip_all_tags( $gateways[ $id ]->get_title() ),
				);
			}
		}

		return $ways;
	}

	/**
	 * Whether an order can be marked as paid here: not paid yet, waiting for payment, and above zero.
	 *
	 * @param WC_Order $order The order.
	 * @return true|WP_Error
	 */
	public static function can_mark_paid( WC_Order $order ) {
		if ( $order->get_date_paid() ) {
			return new WP_Error( 'ioefwp_paid', __( 'This order is already marked as paid.', 'inline-order-editor-pro' ) );
		}

		$waiting = $order->needs_payment() || ( $order->has_status( 'on-hold' ) && (float) $order->get_total() > 0 );

		if ( ! $waiting ) {
			return new WP_Error( 'ioefwp_paid', __( 'This order is not waiting for payment.', 'inline-order-editor-pro' ) );
		}

		return true;
	}

	/**
	 * Marks an order as paid, and records how.
	 *
	 * @param WC_Order $order The order.
	 * @param string   $way   A key of payment_ways(), such as cash.
	 * @return WC_Order|WP_Error The order, read again after the change.
	 */
	public static function mark_paid( WC_Order $order, $way ) {
		$ways     = self::payment_ways();
		$can_mark = self::can_mark_paid( $order );

		if ( is_wp_error( $can_mark ) ) {
			return $can_mark;
		}

		if ( ! isset( $ways[ $way ] ) ) {
			return new WP_Error( 'ioefwp_paid', __( 'That way of paying is not switched on in this shop.', 'inline-order-editor-pro' ) );
		}

		$user = wp_get_current_user();

		$order->set_payment_method( $ways[ $way ]['id'] );
		$order->set_payment_method_title( $ways[ $way ]['title'] );
		$order->save();
		$order->add_order_note(
			sprintf(
				/* translators: 1: order total, 2: how it was paid, 3: name of the person who recorded it. */
				__( 'Marked as paid: %1$s by %2$s. Recorded by %3$s.', 'inline-order-editor-pro' ),
				IOEFW_Orders::plain_price( $order->get_total(), $order ),
				$ways[ $way ]['title'],
				$user && $user->exists() ? $user->display_name : __( 'the shop', 'inline-order-editor-pro' )
			),
			false,
			true
		);
		$order->payment_complete();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Runs a mark as paid action.
	 *
	 * @param mixed  $order The order the action was chosen on.
	 * @param string $way   A key of payment_ways().
	 */
	public static function handle_mark_paid( $order, $way ) {
		if ( $order instanceof WC_Order && current_user_can( 'edit_shop_orders' ) ) {
			self::mark_paid( $order, $way );
		}
	}

	/* ---------- Typed items as draft products ---------- */

	/**
	 * The typed items on an order that have not been saved as a draft product yet.
	 *
	 * @param WC_Order $order The order.
	 * @return WC_Order_Item_Product[]
	 */
	public static function typed_items_without_product( WC_Order $order ) {
		$items = array();

		foreach ( $order->get_items() as $item ) {
			if ( 'yes' === $item->get_meta( IOEFW_Orders::CUSTOM_ITEM_META ) && ! $item->get_meta( self::PRODUCT_META ) ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * Saves each typed item on an order as a draft product. Nothing is published, and the order's
	 * lines stay as they are.
	 *
	 * @param WC_Order $order The order.
	 * @return int[]|WP_Error The IDs of the draft products made.
	 */
	public static function save_typed_items_as_drafts( WC_Order $order ) {
		if ( ! current_user_can( 'edit_products' ) ) {
			return new WP_Error( 'ioefwp_drafts', __( 'You are not allowed to add products.', 'inline-order-editor-pro' ) );
		}

		$made  = array();
		$names = array();

		foreach ( self::typed_items_without_product( $order ) as $item ) {
			$quantity = (float) $item->get_quantity();
			$line     = (float) $item->get_subtotal() + ( wc_prices_include_tax() ? (float) $item->get_subtotal_tax() : 0 );
			$product  = new WC_Product_Simple();

			$product->set_name( $item->get_name() );
			$product->set_status( 'draft' );
			$product->set_regular_price( wc_format_decimal( $quantity > 0 ? $line / $quantity : $line, wc_get_price_decimals() ) );
			$product->set_tax_class( $item->get_tax_class() );
			$product->save();

			if ( ! $product->get_id() ) {
				continue;
			}

			$item->add_meta_data( self::PRODUCT_META, $product->get_id(), true );
			$item->save();

			$made[]  = $product->get_id();
			$names[] = sprintf( '%1$s (#%2$d)', $item->get_name(), $product->get_id() );
		}

		if ( $made ) {
			/* translators: %s: list of product names with their IDs. */
			$order->add_order_note( sprintf( __( 'Saved as draft products, not published: %s.', 'inline-order-editor-pro' ), implode( ', ', $names ) ), false, true );
		}

		return $made;
	}

	/**
	 * Runs the save as draft products action.
	 *
	 * @param mixed $order The order the action was chosen on.
	 */
	public static function handle_save_drafts( $order ) {
		if ( $order instanceof WC_Order && current_user_can( 'edit_shop_orders' ) ) {
			self::save_typed_items_as_drafts( $order );
		}
	}
}
