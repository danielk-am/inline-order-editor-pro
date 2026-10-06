/**
 * Inline Order Editor Pro: the Saved terms rows on WooCommerce > Settings > General.
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		const target = event.target;

		if ( ! target || ! target.closest ) {
			return;
		}

		const field = target.closest( '.ioefwp-saved-terms' );

		if ( ! field ) {
			return;
		}

		const rows = field.querySelector( '.ioefwp-saved-terms__rows' );

		if ( target.closest( '.ioefwp-saved-terms__add' ) ) {
			const last = rows.lastElementChild;
			const copy = last.cloneNode( true );
			const index = rows.children.length;

			// A fresh, empty row with its own place in the list.
			copy.querySelectorAll( 'input, textarea' ).forEach( function ( input ) {
				input.value = '';
				input.name = input.name.replace( /\[\d+\]/, '[' + index + ']' );
			} );
			rows.appendChild( copy );
			copy.querySelector( 'input' ).focus();
		} else if ( target.closest( '.ioefwp-saved-terms__remove' ) ) {
			const row = target.closest( '.ioefwp-saved-terms__row' );

			if ( rows.children.length > 1 ) {
				rows.removeChild( row );
			} else {
				// Keep one row to type in. Empty rows are not saved.
				row.querySelectorAll( 'input, textarea' ).forEach( function ( input ) {
					input.value = '';
				} );
			}
		}
	} );
} )();
