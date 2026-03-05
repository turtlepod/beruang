/**
 * Beruang frontend utility functions.
 *
 * @package Beruang
 */

'use strict';

import { restUrl, restNonce, beruangData, getDecimalPlaces } from './config.js';

export function escapeHtml( str ) {
	return String( str )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' )
		.replace( /'/g, '&#039;' );
}

export function beruangTemplate( name ) {
	const script = document.getElementById( 'tmpl-' + name );
	if ( ! script ) return function () { return ''; };
	const html = script.textContent || script.innerText || '';
	return function ( data ) {
		data = data || {};
		return html
			.replace( /\{\{\{\s*data\.(\w+)\s*\}\}\}/g, function ( _, key ) {
				const val = data[ key ];
				return val !== undefined && val !== null ? String( val ) : '';
			} )
			.replace( /\{\{\s*data\.(\w+)\s*\}\}/g, function ( _, key ) {
				const val = data[ key ];
				return escapeHtml( val !== undefined && val !== null ? val : '' );
			} );
	};
}

export function request( method, path, data ) {
	data = data || {};
	let url = restUrl + path;
	const opts = {
		method,
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': restNonce,
		},
	};
	if ( method === 'POST' || method === 'PUT' ) {
		if ( Object.keys( data ).length ) opts.body = JSON.stringify( data );
	}
	if ( method === 'GET' && Object.keys( data ).length ) {
		url += '?' + new URLSearchParams( data ).toString();
	}
	return fetch( url, opts )
		.then( function ( r ) {
			if ( ! r.ok ) {
				return r
					.json()
					.then( function ( body ) {
						const msg =
							body && body.data && body.data.message
								? body.data.message
								: ( body && body.message ) || 'Error';
						return { success: false, data: { message: msg } };
					} )
					.catch( function () {
						return { success: false, data: { message: 'Error' } };
					} );
			}
			return r.json();
		} )
		.catch( function () {
			return { success: false, data: { message: 'Error' } };
		} );
}

/**
 * Show or hide loading state on a form (opacity + spinner overlay).
 *
 * @param {HTMLFormElement} form  Form element.
 * @param {boolean}        loading Whether loading.
 */
export function setFormLoading( form, loading ) {
	if ( ! form ) return;
	let overlay = form.querySelector( '.beruang-form-loading' );
	if ( loading ) {
		form.classList.add( 'is-loading' );
		if ( ! overlay ) {
			overlay = document.createElement( 'div' );
			overlay.className = 'beruang-form-loading';
			overlay.setAttribute( 'aria-hidden', 'true' );
			overlay.innerHTML =
				'<span class="beruang-form-spinner" aria-hidden="true"></span>';
			form.appendChild( overlay );
		}
		overlay.hidden = false;
	} else {
		form.classList.remove( 'is-loading' );
		if ( overlay ) overlay.hidden = true;
	}
}

export function formatNum( n ) {
	const dec = beruangData.decimal_sep || ',';
	const thou = beruangData.thousands_sep || '.';
	const places = getDecimalPlaces();
	const num = Number( n );
	const s =
		0 === places
			? String( Math.round( num ) )
			: num.toFixed( places );
	const parts = s.split( '.' );
	parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, thou );
	return parts.join( dec ) + ' ' + ( beruangData.currency || 'IDR' );
}

/**
 * Format amount for HTML number input value (respects decimal places setting).
 * Uses dot as decimal separator, no thousands separator.
 *
 * @param {number|string} n Amount value.
 * @return {string}
 */
export function formatAmountForInput( n ) {
	const places = getDecimalPlaces();
	const num = Number( n );
	if ( Number.isNaN( num ) ) return '';
	return 0 === places ? String( Math.round( num ) ) : num.toFixed( places );
}
