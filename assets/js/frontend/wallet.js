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
		if ( submitBtn ) submitBtn.textContent = i18n.update_wallet || 'Update wallet';
		clearMessage();
		modal.hidden = false;
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
			html += walletItemTpl( {
				id: wallet.id,
				name: wallet.name || '',
				displayName: wallet.name || '',
				actionsHtml,
			} );
		} );
		list.innerHTML = html;
	}

	function refreshWallets() {
		request( 'GET', '/wallets' ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.wallets ) return;
			const defaultWalletId = r.data.default_wallet_id || '0';
			list.dataset.defaultWalletId = String( defaultWalletId );
			renderWallets( r.data.wallets );
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
