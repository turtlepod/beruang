/**
 * Beruang wallet management page.
 *
 * @package Beruang
 */

'use strict';

import { i18n, editIcon, deleteIcon } from './config.js';
import { request, beruangTemplate, setFormLoading, escapeHtml, formatNum } from './utils.js';

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
	const submitBtn = form.querySelector( '.beruang-wallet-submit-add' );
	const cancelBtn = form.querySelector( '.beruang-wallet-cancel-edit' );
	const message = form.querySelector( '.beruang-form-message' );
	const defaultWalletSelect = document.getElementById( 'beruang-default-wallet-select' );
	const defaultWalletRow = document.querySelector( '.beruang-wallet-default-row' );

	function clearMessage() {
		if ( ! message ) return;
		message.textContent = '';
		message.style.color = '';
	}

	function getTodayDate() {
		const now = new Date();
		return now.getFullYear() + '-' +
			String( now.getMonth() + 1 ).padStart( 2, '0' ) + '-' +
			String( now.getDate() ).padStart( 2, '0' );
	}

	function resetForm() {
		if ( editIdEl ) editIdEl.value = '';
		if ( nameEl ) nameEl.value = '';
		if ( initialAmountEl ) initialAmountEl.value = '';
		if ( initialDateEl ) initialDateEl.value = getTodayDate();
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
		if ( initialAmountEl ) initialAmountEl.value = item.dataset.initialAmount || '';
		if ( initialDateEl ) initialDateEl.value = item.dataset.initialDate || getTodayDate();
		if ( setDefaultEl ) {
			const currentDefaultId = defaultWalletSelect
				? defaultWalletSelect.dataset.defaultWalletId
				: list.dataset.defaultWalletId;
			setDefaultEl.checked = !! currentDefaultId && String( item.dataset.id ) === String( currentDefaultId );
		}
		if ( submitBtn ) submitBtn.textContent = i18n.update_wallet || 'Update wallet';
		clearMessage();
		modal.hidden = false;
	}

	function renderWallets( wallets, defaultWalletId ) {
		if ( ! wallets.length ) {
			list.innerHTML = walletEmptyTpl( { message: i18n.no_wallets || 'No wallet created.' } );
			return;
		}
		const defaultId = defaultWalletId ? String( defaultWalletId ) : '';
		let html = '';
		wallets.forEach( function ( wallet ) {
			const actionsHtml = '<button type="button" class="beruang-action-edit" title="' + ( i18n.edit || 'Edit' ) + '" aria-label="' + ( i18n.edit || 'Edit' ) + '">' + editIcon + '</button>' +
				'<button type="button" class="beruang-action-delete" title="' + ( i18n.delete || 'Delete' ) + '" aria-label="' + ( i18n.delete || 'Delete' ) + '">' + deleteIcon + '</button>';
			const walletId = String( wallet.id );
			const initialAmount = wallet.initial_amount !== undefined ? wallet.initial_amount : 0;
			const initialDate = wallet.initial_date || '';
			const currentAmount = wallet.current_amount !== undefined ? wallet.current_amount : initialAmount;
			const baselineTpl = i18n.wallet_baseline || 'Baseline: %1$s on %2$s';
			const metaLabel = baselineTpl.replace( '%1$s', formatNum( initialAmount ) ).replace( '%2$s', initialDate );
			const currentTpl = i18n.wallet_current || 'Current: %s';
			const balanceLabel = currentTpl.replace( '%s', formatNum( currentAmount ) );
			const balanceClass = currentAmount >= 0 ? 'positive' : 'negative';
			html += walletItemTpl( {
				id: walletId,
				name: wallet.name || '',
				isDefault: defaultId && walletId === defaultId ? '1' : '0',
				displayName: wallet.name || '',
				initialAmount: String( initialAmount ),
				initialDate,
				metaLabel,
				balanceLabel,
				balanceClass,
				actionsHtml,
			} );
		} );
		list.innerHTML = html;
	}

	function updateDefaultSelect( wallets, defaultWalletId ) {
		if ( defaultWalletRow ) {
			defaultWalletRow.hidden = wallets.length < 2;
		}
		if ( ! defaultWalletSelect ) return;
		const currentDefault = defaultWalletId ? String( defaultWalletId ) : '';
		let html = '<option value="">' + escapeHtml( i18n.no_wallet || 'No Wallet' ) + '</option>';
		wallets.forEach( function ( wallet ) {
			html += '<option value="' + escapeHtml( String( wallet.id ) ) + '"' +
				( String( wallet.id ) === currentDefault ? ' selected' : '' ) +
				'>' + escapeHtml( wallet.name || '' ) + '</option>';
		} );
		defaultWalletSelect.innerHTML = html;
		defaultWalletSelect.value = currentDefault;
		defaultWalletSelect.dataset.defaultWalletId = currentDefault;
	}

	function refreshWallets() {
		request( 'GET', '/wallets' ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.wallets ) return;
			renderWallets( r.data.wallets, r.data.default_wallet_id );
			updateDefaultSelect( r.data.wallets, r.data.default_wallet_id );
			document.dispatchEvent( new CustomEvent( 'beruang-wallets-updated' ) );
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
			initial_amount: initialAmountEl ? parseFloat( initialAmountEl.value ) || 0 : 0,
			initial_date: initialDateEl ? initialDateEl.value : '',
			set_as_default: setDefaultEl ? setDefaultEl.checked : false,
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
			const item = editBtn.closest( '.beruang-wallet-card' );
			if ( ! item ) return;
			openEditModal( item );
			return;
		}

		const deleteBtn = e.target.closest( '.beruang-action-delete' );
		if ( ! deleteBtn ) return;
		const item = deleteBtn.closest( '.beruang-wallet-card' );
		if ( ! item || ! item.dataset.id || item.dataset.default === '1' ) return;
		if ( ! confirm( i18n.confirm_delete_wallet || 'Delete this wallet?' ) ) return;
		request( 'DELETE', '/wallets/' + item.dataset.id ).then( function ( r ) {
			if ( r.success ) {
				refreshWallets();
			}
		} );
	} );

	if ( defaultWalletSelect ) {
		defaultWalletSelect.addEventListener( 'change', function () {
			const walletId = this.value || null;
			request( 'POST', '/wallets/default', { wallet_id: walletId } ).then( function ( r ) {
				if ( r.success ) {
					refreshWallets();
				}
			} );
		} );
	}

	refreshWallets();
	document.addEventListener( 'beruang-transaction-saved', refreshWallets );
}
