#!/usr/bin/env node
/**
 * Captures the WooCommerce.com Marketplace gallery images from the Playground test store.
 *
 * Start a fresh store with this add-on and the free plugin mounted (see README.md), then run:
 * node design/screenshots.mjs
 * Leave other plugins out of that store. Their boxes and notices would be in the pictures.
 * It seeds the free plugin's sample shop, works on its order with real mouse and key events, and
 * writes design/gallery/gallery-1.png to gallery-7.png at 1280 x 860. The Marketplace asks for
 * 896 x 550 or larger. The store is changed along the way, so start it again before another run.
 *
 * Needs Node 22 or later and Google Chrome. Set CHROME_BIN or IOEFW_STORE to override the defaults.
 */
import { spawn } from 'node:child_process';
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const STORE = process.env.IOEFW_STORE || 'http://127.0.0.1:9414';
const CHROME =
	process.env.CHROME_BIN ||
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = join( dirname( fileURLToPath( import.meta.url ) ), 'gallery' );
const PORT = 9336;
const VIEWPORT = { width: 1280, height: 860 };

const KEYS = {
	Enter: { code: 'Enter', keyCode: 13, text: '\r' },
	Tab: { code: 'Tab', keyCode: 9 },
	Escape: { code: 'Escape', keyCode: 27 },
};

const SAVED_TERMS = [
	[
		'Wedding orders',
		'50% deposit to confirm the date. The balance is due 7 days before the wedding. Delivery and set-up are included within Singapore.',
	],
	[
		'Trade customers',
		'Payment within 30 days of invoice. Prices exclude delivery. Collection from the shop, Monday to Saturday.',
	],
];

const sleep = ( ms ) => new Promise( ( resolve ) => setTimeout( resolve, ms ) );

mkdirSync( OUT, { recursive: true } );

const profile = mkdtempSync( join( tmpdir(), 'ioefwp-screenshots-' ) );
const chrome = spawn(
	CHROME,
	[
		'--headless=new',
		`--remote-debugging-port=${ PORT }`,
		`--user-data-dir=${ profile }`,
		`--window-size=${ VIEWPORT.width },${ VIEWPORT.height }`,
		'--hide-scrollbars',
		'--no-first-run',
		'--disable-gpu',
		'about:blank',
	],
	{ stdio: 'ignore' }
);

async function connect() {
	for ( let attempt = 0; attempt < 50; attempt++ ) {
		try {
			const targets = await (
				await fetch( `http://127.0.0.1:${ PORT }/json/list` )
			 ).json();
			const page = targets.find( ( target ) => 'page' === target.type );
			if ( page ) {
				return new WebSocket( page.webSocketDebuggerUrl );
			}
		} catch {
			// Chrome is still starting.
		}
		await sleep( 200 );
	}
	throw new Error( 'Chrome did not start.' );
}

const socket = await connect();
await new Promise( ( resolve ) => socket.addEventListener( 'open', resolve ) );

let lastId = 0;
const pending = new Map();
const waiting = new Map();

const send = ( method, params = {} ) =>
	new Promise( ( resolve, reject ) => {
		const id = ++lastId;
		pending.set( id, { resolve, reject } );
		socket.send( JSON.stringify( { id, method, params } ) );
	} );

socket.addEventListener( 'message', ( event ) => {
	const message = JSON.parse( event.data );

	if ( message.id && pending.has( message.id ) ) {
		const { resolve, reject } = pending.get( message.id );
		pending.delete( message.id );
		if ( message.error ) {
			reject( new Error( message.error.message ) );
		} else {
			resolve( message.result );
		}
	} else if ( 'Page.javascriptDialogOpening' === message.method ) {
		// A "leave this page?" prompt would stop the run. Nothing here is left unsaved on purpose.
		send( 'Page.handleJavaScriptDialog', { accept: true } ).catch( () => {} );
	} else if ( message.method && waiting.has( message.method ) ) {
		const resolvers = waiting.get( message.method );
		waiting.delete( message.method );
		resolvers.forEach( ( resolve ) => resolve( message.params ) );
	}
} );

const once = ( method ) =>
	new Promise( ( resolve ) => {
		waiting.set( method, [ ...( waiting.get( method ) || [] ), resolve ] );
	} );

async function goTo( path ) {
	const loaded = once( 'Page.loadEventFired' );
	await send( 'Page.navigate', { url: STORE + path } );
	await loaded;
	await sleep( 1500 );
}

// Runs a function in the page. It must be self-contained: only its arguments cross over.
async function inPage( fn, ...args ) {
	const { result, exceptionDetails } = await send( 'Runtime.evaluate', {
		expression: `(${ fn })(...${ JSON.stringify( args ) })`,
		awaitPromise: true,
		returnByValue: true,
	} );

	if ( exceptionDetails ) {
		throw new Error(
			exceptionDetails.exception?.description || exceptionDetails.text
		);
	}

	return result.value;
}

async function waitFor( what, fn, ...args ) {
	for ( let attempt = 0; attempt < 120; attempt++ ) {
		if ( await inPage( fn, ...args ) ) {
			return;
		}
		await sleep( 250 );
	}
	throw new Error( `Timed out waiting for ${ what }.` );
}

async function press( key ) {
	const { code, keyCode, text } = KEYS[ key ];
	const event = { key, code, windowsVirtualKeyCode: keyCode, nativeVirtualKeyCode: keyCode };

	await send( 'Input.dispatchKeyEvent', { type: text ? 'keyDown' : 'rawKeyDown', text, ...event } );
	await send( 'Input.dispatchKeyEvent', { type: 'keyUp', ...event } );
	await sleep( 150 );
}

async function type( text ) {
	for ( const character of text ) {
		await send( 'Input.insertText', { text: character } );
		await sleep( 15 );
	}
}

// The middle of an element, in viewport coordinates, after scrolling it into view.
const middleOf = ( selector ) =>
	inPage( ( query ) => {
		const element = document.querySelector( query );
		element.scrollIntoView( { block: 'center' } );
		const box = element.getBoundingClientRect();
		return { x: box.left + box.width / 2, y: box.top + box.height / 2 };
	}, selector );

async function click( selector ) {
	const { x, y } = await middleOf( selector );

	await send( 'Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 } );
	await send( 'Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 } );
	await sleep( 200 );
}

// A click that submits a form: wait for the page that comes back.
async function clickAndLoad( selector ) {
	const loaded = once( 'Page.loadEventFired' );
	await click( selector );
	await loaded;
	await sleep( 1500 );
}

// Scrolls so the element sits a little below the admin bar, and parks the pointer out of the way.
async function frame( selector, offset = 70 ) {
	await inPage(
		( query, gap ) => {
			const top = document.querySelector( query ).getBoundingClientRect().top + window.scrollY;
			window.scrollTo( 0, Math.max( 0, top - gap ) );
		},
		selector,
		offset
	);
	await send( 'Input.dispatchMouseEvent', { type: 'mouseMoved', x: VIEWPORT.width - 4, y: VIEWPORT.height - 4 } );
	await sleep( 300 );
}

async function capture( number ) {
	const { data } = await send( 'Page.captureScreenshot', { format: 'png' } );
	writeFileSync( join( OUT, `gallery-${ number }.png` ), Buffer.from( data, 'base64' ) );
	console.log( `gallery-${ number }.png` );
}

// A menu's own list opens outside the page, where it cannot be driven or captured. Choose the entry
// by its label and tell the page it changed, as picking it would.
const choose = ( selector, label ) =>
	inPage(
		( query, text ) => {
			const menu = document.querySelector( query );
			const option = [ ...menu.options ].find( ( one ) => one.textContent.trim() === text );
			if ( ! option ) {
				throw new Error( `No "${ text }" in ${ query }.` );
			}
			menu.value = option.value;
			menu.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		},
		selector,
		label
	);

// A message from an earlier step would sit in the next picture. They go away by themselves.
const messageGone = () =>
	waitFor( 'the last message to go', () => ! document.querySelector( '.components-snackbar, .ioefw-toast:not([hidden])' ) );

const ITEMS = '#woocommerce-order-items';
const NAME = `${ ITEMS } .ioefw-add__name`;
const ACTIONS = 'select[name="wc_order_action"]';
const UPDATE = '#woocommerce-order-actions button.save_order';
const ROWS = '.ioefwp-saved-terms__row';
const orderPath = ( id ) => `/wp-admin/admin.php?page=wc-orders&action=edit&id=${ id }`;
const orderId = () => inPage( () => new URLSearchParams( location.search ).get( 'id' ) );

try {
	await send( 'Page.enable' );
	await send( 'Runtime.enable' );
	await send( 'Emulation.setDeviceMetricsOverride', {
		...VIEWPORT,
		deviceScaleFactor: 1,
		mobile: false,
	} );

	// The store logs the first visitor in.
	await goTo( '/' );
	await goTo( '/wp-admin/admin-post.php?action=ioefw_dev&script=seed' );

	const order = await inPage( () => ( document.body.innerText.match( /SEED_ORDER=(\d+)/ ) || [] )[ 1 ] );

	if ( ! order ) {
		throw new Error( 'The seed script did not report its order.' );
	}

	// 4. Two sets of saved terms, typed into the setting and saved.
	await goTo( '/wp-admin/admin.php?page=wc-settings&tab=general' );
	await waitFor( 'the Saved terms setting', ( query ) => !! document.querySelector( query ), ROWS );

	for ( const [ index, [ name, terms ] ] of SAVED_TERMS.entries() ) {
		if ( index ) {
			await click( '.ioefwp-saved-terms__add' );
			await waitFor( 'another row', ( query, count ) => document.querySelectorAll( query ).length > count, ROWS, index );
		}
		await click( `${ ROWS }:nth-of-type(${ index + 1 }) input[type="text"]` );
		await type( name );
		await click( `${ ROWS }:nth-of-type(${ index + 1 }) textarea` );
		await type( terms );
	}

	await clickAndLoad( '.woocommerce-save-button' );
	await waitFor(
		'the saved terms to come back',
		( query, count ) => [ ...document.querySelectorAll( `${ query } input[type="text"]` ) ].filter( ( input ) => input.value ).length === count,
		ROWS,
		SAVED_TERMS.length
	);
	await inPage( () => document.activeElement && document.activeElement.blur() );
	await frame( '.ioefwp-saved-terms', 150 );
	await capture( 4 );

	// A typed item on the sample order, for the draft product later.
	await goTo( orderPath( order ) );
	await waitFor( 'the new row', ( query ) => !! document.querySelector( query ), NAME );
	await click( NAME );
	await type( 'Glass vase rental' );
	await press( 'Tab' );
	await type( '12.50' );
	await press( 'Tab' );
	await type( '2' );
	await press( 'Enter' );
	await waitFor( 'the typed item', () => /Glass vase rental/.test( document.querySelector( '#order_line_items' ).innerText ) );

	// 1. The Add discount form, filled in.
	await messageGone();
	await click( `${ ITEMS } .ioefwp-discount-open` );
	await waitFor( 'the discount form', ( query ) => ! document.querySelector( query ).hidden, `${ ITEMS } .ioefwp-discount-form` );
	await type( '10%' );
	await click( `${ ITEMS } .ioefwp-discount-label` );
	await type( 'Regular customer' );
	await frame( ITEMS );
	await capture( 1 );

	// 2. The discount as its own line, and the message that says what it did to the total.
	await click( `${ ITEMS } .ioefwp-discount-apply` );
	await waitFor( 'the discount line', () => /Regular customer/.test( document.querySelector( '#order_fee_line_items' ).innerText ) );
	await waitFor( 'the discount message', () => /Discount added\./.test( ( document.querySelector( '.components-snackbar, .ioefw-toast' ) || {} ).textContent || '' ) );
	await inPage( () => document.activeElement && document.activeElement.blur() );
	await frame( ITEMS );
	await sleep( 700 );
	await capture( 2 );

	// 3. Saved terms picked on the order. Closing the Custom Fields box brings the terms up beside the items.
	await messageGone();
	const fields = await inPage( () => {
		const box = [ ...document.querySelectorAll( '.postbox' ) ].find( ( one ) => /Custom Fields/.test( ( one.querySelector( 'h2' ) || {} ).textContent || '' ) );
		if ( box && ! box.classList.contains( 'closed' ) ) {
			box.id = box.id || 'ioefwp-custom-fields-box';
			return '#' + box.id + ' .handlediv';
		}
		return '';
	} );
	if ( fields ) {
		await click( fields );
	}
	await choose( '#ioefw-order-terms .ioefwp-saved-terms-picker', SAVED_TERMS[ 0 ][ 0 ] );
	await waitFor( 'the terms to be filled in', ( text ) => document.getElementById( 'ioefw-order-terms-text' ).value === text, SAVED_TERMS[ 0 ][ 1 ] );
	await inPage( () => document.activeElement && document.activeElement.blur() );
	await frame( '#ioefw-order-terms', 120 );
	await capture( 3 );
	await clickAndLoad( UPDATE );

	// 6. The typed item as a draft product.
	await waitFor( 'the order actions', ( query ) => !! document.querySelector( query ), ACTIONS );
	await choose( ACTIONS, 'Save typed items as draft products' );
	await clickAndLoad( UPDATE );
	await goTo( '/wp-admin/edit.php?post_type=product&post_status=draft' );
	await waitFor( 'the draft product', () => /Glass vase rental/.test( ( document.querySelector( '.wp-list-table' ) || {} ).innerText || '' ) );
	await inPage( () => window.scrollTo( 0, 0 ) );
	await send( 'Input.dispatchMouseEvent', { type: 'mouseMoved', x: VIEWPORT.width - 4, y: VIEWPORT.height - 4 } );
	await sleep( 300 );
	await capture( 6 );

	// 5. The order repeated as a new one.
	await goTo( orderPath( order ) );
	await waitFor( 'the order actions', ( query ) => !! document.querySelector( query ), ACTIONS );
	await choose( ACTIONS, 'Repeat this order as a new order' );
	await clickAndLoad( UPDATE );
	await waitFor( 'the new order', ( first ) => new URLSearchParams( location.search ).get( 'id' ) !== first && !! document.querySelector( '#woocommerce-order-notes' ), order );
	const repeated = await orderId();
	await frame( '#woocommerce-order-notes', 70 );
	await capture( 5 );

	// 7. The new order marked as paid.
	await choose( ACTIONS, 'Mark as paid: Cash' );
	await clickAndLoad( UPDATE );
	await waitFor( 'the paid note', () => /Marked as paid: .+ by Cash/.test( ( document.querySelector( '#woocommerce-order-notes' ) || {} ).innerText || '' ) );
	await frame( '#woocommerce-order-notes', 70 );
	await capture( 7 );

	console.log( `sample order ${ order }, repeated as ${ repeated }` );
} finally {
	socket.close();
	chrome.kill();
	await sleep( 300 );
	rmSync( profile, { recursive: true, force: true } );
}
