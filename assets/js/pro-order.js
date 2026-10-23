/**
 * Alkamy Order Editor Pro for WooCommerce: the order screen.
 *
 * The saved terms picker and the Add discount form. Both lean on Alkamy Order Editor's own script
 * for drawing the Items box and for the line that says what happened.
 */
( function ( $, wp, settings ) {
	'use strict';

	if ( ! settings || ! wp || ! wp.i18n ) {
		return;
	}

	const __ = wp.i18n.__;
	const sprintf = wp.i18n.sprintf;
	const BOX = '#woocommerce-order-items';
	let busy = false;

	function editor() {
		return window.ioefw || {};
	}

	function say( text, type ) {
		if ( editor().notice ) {
			editor().notice( text, type );
		}
	}

	function form() {
		return document.querySelector( BOX + ' .ioefwp-discount-form' );
	}

	function field( name ) {
		return document.querySelector( BOX + ' .ioefwp-discount-' + name );
	}

	function showForm( show ) {
		const element = form();
		const open = field( 'open' );

		if ( ! element || ! open ) {
			return;
		}

		// The form has a line of its own under the totals, so the order's buttons stay where they are.
		( element.closest( '.ioefwp-discount-row' ) || element ).hidden = ! show;
		open.hidden = show;

		if ( show ) {
			field( 'amount' ).focus();
		} else {
			open.focus();
		}
	}

	// What the toast says after a discount: what it did to the total, and on an order that does not
	// exist yet, what keeps it.
	function addedText( data ) {
		const state = editor().getState ? editor().getState() : null;
		let text = __( 'Discount added.', 'inline-order-editor-pro' );

		if ( data && data.total && data.totalBefore && data.total !== data.totalBefore ) {
			text = sprintf(
				/* translators: 1: new order total, 2: order total before the discount. */
				__( 'Discount added. The order total is now %1$s. It was %2$s.', 'inline-order-editor-pro' ),
				data.total,
				data.totalBefore
			);
		}

		return state && state.isNew
			? text + ' ' + __( 'Click Create to keep this order.', 'inline-order-editor-pro' )
			: text;
	}

	function applyDiscount() {
		const amount = field( 'amount' );
		const label = field( 'label' );

		if ( busy || ! amount ) {
			return;
		}

		if ( '' === amount.value.trim() ) {
			say( __( 'Type a discount first, such as 10 or 10%.', 'inline-order-editor-pro' ), 'error' );
			amount.focus();
			return;
		}

		const body = new URLSearchParams();
		const address = editor().taxAddress ? editor().taxAddress() : {};

		body.append( 'action', 'ioefwp_add_discount' );
		body.append( 'security', settings.nonce );
		body.append( 'order_id', settings.orderId );
		body.append( 'amount', amount.value.trim() );
		body.append( 'label', label ? label.value.trim() : '' );

		Object.keys( address ).forEach( function ( key ) {
			body.append( key, address[ key ] || '' );
		} );

		busy = true;
		form().setAttribute( 'aria-busy', 'true' );

		window
			.fetch( settings.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.catch( function () {
				return null;
			} )
			.then( function ( response ) {
				const failed = __( 'The discount was not added. Try again.', 'inline-order-editor-pro' );

				busy = false;

				if ( form() ) {
					form().removeAttribute( 'aria-busy' );
				}

				if ( ! response || ! response.success ) {
					say( ( response && response.data && response.data.message ) || failed, 'error' );
					return;
				}

				if ( editor().draw ) {
					editor().draw( response.data );
				} else {
					window.location.reload();
				}

				say( addedText( response.data ) );
			} );
	}

	$( document )
		// Saved terms: fill the box. The order's own Create or Update button saves it.
		.on( 'change', '.ioefwp-saved-terms-picker', function () {
			const terms = document.getElementById( 'ioefw-order-terms-text' );

			if ( terms && '' !== this.value ) {
				terms.value = this.value;
				terms.focus();
			}

			this.selectedIndex = 0;
		} )

		// Add discount.
		.on( 'click', BOX + ' .ioefwp-discount-open', function () {
			showForm( true );
		} )
		.on( 'click', BOX + ' .ioefwp-discount-cancel', function () {
			showForm( false );
		} )
		.on( 'click', BOX + ' .ioefwp-discount-apply', applyDiscount )
		.on( 'keydown', BOX + ' .ioefwp-discount-form input', function ( event ) {
			if ( event.isComposing || 229 === event.keyCode ) {
				return;
			}

			if ( 'Enter' === event.key ) {
				// Never let Enter reach the order form: it would save the whole order.
				event.preventDefault();
				event.stopPropagation();
				applyDiscount();
			} else if ( 'Escape' === event.key ) {
				event.preventDefault();
				showForm( false );
			}
		} );
} )( window.jQuery, window.wp, window.ioefwpSettings );
