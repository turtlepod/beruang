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
	const p = parseInt( beruang.decimal_places, 10 );
	return p >= 0 && p <= 4 ? p : 2;
}
