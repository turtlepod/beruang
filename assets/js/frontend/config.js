/**
 * Beruang frontend configuration (from wp_localize_script).
 *
 * @package Beruang
 */

'use strict';

const beruang = window.beruangData || {};

export const restUrl = beruang.rest_url || '';
export const restNonce = beruang.rest_nonce || '';
export const i18n = beruang.i18n || {};
export const beruangData = beruang;
export const editIcon = beruang.edit_icon || '';
export const deleteIcon = beruang.delete_icon || '';

export function getDecimalPlaces() {
	const raw = beruang.decimal_places;
	if ( raw === 0 || raw === '0' ) return 0;
	const p = parseInt( raw, 10 );
	return ! Number.isNaN( p ) && p >= 0 && p <= 4 ? p : 2;
}
