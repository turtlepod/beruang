/**
 * Beruang wallet management page.
 *
 * @package Beruang
 */

'use strict';

import { i18n, editIcon, deleteIcon } from './config.js';
import { request, beruangTemplate, setFormLoading } from './utils.js';

export function initWallet() {
	const form = document.getElementById( 'beruang-wallet-form' );
	const list = document.getElementById( 'beruang-wallet-list' );
	const modal = document.getElementById( 'beruang-wallet-modal' );
	if ( ! form || ! list || ! modal ) return;

	const walletAddBtn = document.querySelector( '.beruang-wallet-add' );
	const walletItemTpl = beruangTemplate( 'beruang-wallet-item' );
	const walletEmptyTpl = beruangTemplate( 'beruang-wallet-empty' );
	const editIdEl = document.getElementById( 'beruang-wallet-edit-id' );
	const nameEl = document.getElementById( 'beruang-wallet-name' );
	const initialAmountEl = document.getElementById( 'beruang-wallet-initial-amount' );
	const initialDateEl = document.getElementById( 'beruang-wallet-initial-date' );
	const setDefaultEl = document.getElementById( 'beruang-wallet-set-default' );
	const defaultSelectEl = document.getElementById( 'beruang-default-wallet-select' );
	const defaultRow = defaultSelectEl ? defaultSelectEl.closest( '.beruang-wallet-default-row' ) : null;
	const submitBtn = form.querySelector( '.beruang-wallet-submit-add' );
	const cancelBtn = form.querySelector( '.beruang-wallet-cancel-edit' );
	const message = form.querySelector( '.beruang-form-message' );

	function clearMessage() {
		if ( ! message ) return;
		message.textContent = '';
		message.style.color = '';
	}

	function resetForm() {
		if ( editIdEl ) editIdEl.value = '';
		if ( nameEl ) nameEl.value = '';
		if ( initialAmountEl ) initialAmountEl.value = '0';
		if ( initialDateEl ) initialDateEl.value = new Date().toISOString().slice( 0, 10 );
		if ( setDefaultEl ) setDefaultEl.checked = false;
		if ( submitBtn ) submitBtn.textContent = i18n.add_wallet || 'Add wallet';
		clearMessage();
	}

	function closeModal() {
		clearMessage();
		modal.hidden = true;
	}

	function openAddModal() {
		resetForm();
		modal.hidden = false;
	}

	function openEditModal( item ) {
		if ( ! item ) return;
		if ( editIdEl ) editIdEl.value = item.dataset.id || '';
		if ( nameEl ) nameEl.value = item.dataset.name || '';
		if ( initialAmountEl ) initialAmountEl.value = item.dataset.initialAmount || '0';
		if ( initialDateEl ) initialDateEl.value = item.dataset.initialDate || new Date().toISOString().slice( 0, 10 );
		if ( setDefaultEl ) {
			const defaultId = list.dataset.defaultWalletId || '';
			setDefaultEl.checked = !! ( defaultId && defaultId === item.dataset.id );
		}
		if ( submitBtn ) submitBtn.textContent = i18n.update_wallet || 'Update wallet';
		clearMessage();
		modal.hidden = false;
	}

	function buildDefaultWalletOptions( wallets, defaultWalletId ) {
		if ( ! defaultSelectEl ) return;
		let html = '<option value="">' + ( i18n.no_wallet || 'No Wallet' ) + '</option>';
		wallets.forEach( function ( wallet ) {
			html += '<option value="' + wallet.id + '">' + ( wallet.name || '' ) + '</option>';
		} );
		defaultSelectEl.innerHTML = html;
		defaultSelectEl.value = defaultWalletId || '';
		defaultSelectEl.dataset.defaultWalletId = defaultWalletId || '';
	}

	function formatAmount( value ) {
		const n = parseFloat( value ) || 0;
		return n.toFixed( 2 );
	}

	function renderWallets( wallets ) {
		if ( ! wallets.length ) {
			list.innerHTML = walletEmptyTpl( {
				message: i18n.no_wallets || 'No wallets yet.',
			} );
			return;
		}
		let html = '';
		wallets.forEach( function ( wallet ) {
			const actionsHtml = '<button type="button" class="beruang-action-edit" title="' + ( i18n.edit || 'Edit' ) + '" aria-label="' + ( i18n.edit || 'Edit' ) + '">' + editIcon + '</button>' +
				'<button type="button" class="beruang-action-delete" title="' + ( i18n.delete || 'Delete' ) + '" aria-label="' + ( i18n.delete || 'Delete' ) + '">' + deleteIcon + '</button>';
			const baselineDisplay = ( i18n.wallet_baseline || 'Baseline: %1$s on %2$s' )
				.replace( '%1$s', formatAmount( wallet.initial_amount ) )
				.replace( '%2$s', wallet.initial_date || '' );
			const currentDisplay = ( i18n.wallet_current || 'Current: %s' )
				.replace( '%s', formatAmount( wallet.current_amount ) );
			html += walletItemTpl( {
				id: wallet.id,
				name: wallet.name || '',
				displayName: wallet.name || '',
				initialAmount: wallet.initial_amount || 0,
				initialDate: wallet.initial_date || '',
				baselineDisplay,
				currentDisplay,
				actionsHtml,
			} );
		} );
		list.innerHTML = html;
	}

	function refreshWallets() {
		request( 'GET', '/wallets' ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.wallets ) return;
			const defaultWalletId = r.data.default_wallet_id ? String( r.data.default_wallet_id ) : '';
			list.dataset.defaultWalletId = defaultWalletId;
			renderWallets( r.data.wallets );
			buildDefaultWalletOptions( r.data.wallets, defaultWalletId );
			if ( defaultRow ) {
				defaultRow.hidden = r.data.wallets.length < 2;
			}
			document.dispatchEvent( new CustomEvent( 'beruang-wallets-updated' ) );
		} );
	}

	if ( defaultSelectEl ) {
		defaultSelectEl.addEventListener( 'change', function () {
			request( 'POST', '/wallets/default', {
				wallet_id: defaultSelectEl.value || null,
			} ).then( function ( r ) {
				if ( r.success ) {
					refreshWallets();
				}
			} );
		} );
	}

	if ( walletAddBtn ) {
		walletAddBtn.addEventListener( 'click', openAddModal );
	}

	if ( cancelBtn ) {
		cancelBtn.addEventListener( 'click', closeModal );
	}

	modal.addEventListener( 'click', function ( e ) {
		if ( e.target === modal ) closeModal();
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		clearMessage();
		setFormLoading( form, true );
		const id = editIdEl ? editIdEl.value : '';
		const payload = {
			id: id || 0,
			name: nameEl ? nameEl.value : '',
			initial_amount: initialAmountEl ? initialAmountEl.value : 0,
			initial_date: initialDateEl ? initialDateEl.value : null,
			set_as_default: !! ( setDefaultEl && setDefaultEl.checked ),
		};
		request( 'POST', '/wallets', payload ).then( function ( r ) {
			if ( r.success ) {
				resetForm();
				closeModal();
				refreshWallets();
			} else if ( message ) {
				message.textContent = ( r.data && r.data.message ) || i18n.error || 'Error';
				message.style.color = '#d63638';
			}
		} ).catch( function () {
			if ( message ) {
				message.textContent = i18n.error || 'Error';
				message.style.color = '#d63638';
			}
		} ).finally( function () {
			setFormLoading( form, false );
		} );
	} );

	list.addEventListener( 'click', function ( e ) {
		const editBtn = e.target.closest( '.beruang-action-edit' );
		if ( editBtn ) {
			const item = editBtn.closest( '.beruang-wallet-item' );
			if ( ! item || item.dataset.default === '1' ) return;
			openEditModal( item );
			return;
		}

		const deleteBtn = e.target.closest( '.beruang-action-delete' );
		if ( ! deleteBtn ) return;
		const item = deleteBtn.closest( '.beruang-wallet-item' );
		if ( ! item || ! item.dataset.id || item.dataset.default === '1' ) return;
		if ( ! confirm( i18n.confirm_delete_wallet || 'Delete this wallet?' ) ) return;
		request( 'DELETE', '/wallets/' + item.dataset.id ).then( function ( r ) {
			if ( r.success ) {
				refreshWallets();
			}
		} );
	} );

	refreshWallets();
}
