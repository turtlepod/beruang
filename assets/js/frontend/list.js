/**
 * Beruang transaction list with filters and edit modal.
 *
 * @package Beruang
 */

'use strict';

import { i18n, beruangData, getDecimalPlaces, editIcon, deleteIcon } from './config.js';
import { request, beruangTemplate, formatNum } from './utils.js';

export function initList() {
	const accordion = document.getElementById( 'beruang-list-accordion' );
	if ( ! accordion ) return;

	const listWrap = accordion.closest( '.beruang-list-wrapper' );
	const filters = listWrap && listWrap.querySelector( '#beruang-list-filters' );
	const filterBtn = listWrap && listWrap.querySelector( '.beruang-filter-btn' );
	const yearSel = listWrap && listWrap.querySelector( '.beruang-filter-year' );
	const searchEl = listWrap && listWrap.querySelector( '.beruang-filter-search' );
	const categoryEl = listWrap && listWrap.querySelector( '.beruang-filter-category' );
	const budgetEl = listWrap && listWrap.querySelector( '.beruang-filter-budget' );
	const walletEl = listWrap && listWrap.querySelector( '.beruang-filter-wallet' );
	if ( filterBtn && filters ) {
		filterBtn.addEventListener( 'click', function () {
			filters.hidden = ! filters.hidden;
		} );
	}

	const msgTpl = beruangTemplate( 'beruang-message' );
	const txItemTpl = beruangTemplate( 'beruang-transaction-item' );
	const accordionMonthTpl = beruangTemplate( 'beruang-accordion-month' );

	function renderList( items ) {
		const byMonth = {};
		items.forEach( function ( tx ) {
			const d = String( tx.date || '' ).trim();
			const parts = d.split( '-' );
			const monthKey =
				parts.length === 3
					? parts[ 0 ] + '-' + String( parseInt( parts[ 1 ], 10 ) ).padStart( 2, '0' )
					: d;
			if ( ! byMonth[ monthKey ] ) byMonth[ monthKey ] = [];
			byMonth[ monthKey ].push( tx );
		} );
		const monthKeys = Object.keys( byMonth ).sort().reverse();
		const now = new Date();
		const currentMonthKey =
			now.getFullYear() + '-' + String( now.getMonth() + 1 ).padStart( 2, '0' );
		const hasCurrentMonth = monthKeys.indexOf( currentMonthKey ) !== -1;
		let html = '';
		monthKeys.forEach( function ( monthKey, idx ) {
			const monthItems = byMonth[ monthKey ];
			let monthTotal = 0;
			monthItems.forEach( function ( tx ) {
				const amt = parseFloat( tx.amount );
				monthTotal += tx.type === 'income' ? amt : -amt;
			} );
			const monthParts = monthKey.split( '-' );
			let monthLabel = monthKey;
			if ( monthParts.length === 2 ) {
				const y = parseInt( monthParts[ 0 ], 10 );
				const m = parseInt( monthParts[ 1 ], 10 );
				const tmpDate = new Date( Date.UTC( y, m - 1, 1 ) );
				const locale = beruangData.locale || 'en-US';
				monthLabel = new Intl.DateTimeFormat( locale, {
					month: 'long',
					year: 'numeric',
					timeZone: 'UTC',
				} ).format( tmpDate );
			}
			let itemsHtml = '';
			monthItems.forEach( function ( tx ) {
				let dateDisplay = '—';
				let timeDisplay = '—';
				if ( tx.date ) {
					const d = String( tx.date ).trim();
					const parts = d.split( '-' );
					if ( parts.length === 3 ) {
						const y = parseInt( parts[ 0 ], 10 );
						const m = parseInt( parts[ 1 ], 10 ) - 1;
						const day = parseInt( parts[ 2 ], 10 );
						const tmpDate = new Date( Date.UTC( y, m, day ) );
						const locale = beruangData.locale || 'en-US';
						dateDisplay = new Intl.DateTimeFormat( locale, {
							weekday: 'short',
							day: 'numeric',
							timeZone: 'UTC',
						} ).format( tmpDate );
					} else {
						dateDisplay = d;
					}
				}
				if ( tx.time && String( tx.time ).trim() ) {
					const t = String( tx.time ).trim();
					timeDisplay = t.substring( 0, 5 );
				}
				itemsHtml += txItemTpl( {
					id: tx.id,
					dateDisplay,
					timeDisplay,
					description: tx.description || '—',
					amountDisplay:
						( tx.type === 'income' ? '+' : '-' ) +
						formatNum( Math.abs( parseFloat( tx.amount ) ) ),
					type: tx.type,
					editLabel: i18n.edit || 'Edit',
					deleteLabel: i18n.delete || 'Delete',
					editIcon,
					deleteIcon,
				} );
			} );
			const expanded =
				monthKey === currentMonthKey || ( ! hasCurrentMonth && idx === 0 );
			html += accordionMonthTpl( {
				monthKey,
				monthLabel,
				monthTotal: formatNum( monthTotal ),
				itemsHtml,
				monthClass: expanded ? ' is-open' : '',
				expandedAttr: expanded ? 'true' : 'false',
			} );
		} );
		if ( ! monthKeys.length )
			html = msgTpl( { message: i18n.no_transactions || 'No transactions.' } );
		accordion.innerHTML = html;
	}

	function loadList() {
		const year = yearSel
			? parseInt( yearSel.value, 10 )
			: parseInt( accordion.dataset.year, 10 );
		const search = searchEl ? searchEl.value : '';
		const categoryId = categoryEl ? categoryEl.value : '';
		const budgetId = budgetEl ? budgetEl.value : '';
		const walletId = walletEl ? walletEl.value : '';
		accordion.innerHTML = msgTpl( { message: i18n.loading || 'Loading…' } );
		const params = { year, search, category_id: categoryId, budget_id: budgetId, wallet_id: walletId, page: 1 };
		let allItems = [];
		function fetchPage( pageNum ) {
			params.page = pageNum;
			return request( 'GET', '/transactions', params ).then( function ( r ) {
				if ( ! r.success || ! r.data || ! r.data.items ) {
					accordion.innerHTML = msgTpl( { message: i18n.error || 'Error' } );
					return null;
				}
				allItems = allItems.concat( r.data.items );
				const total = r.data.total || 0;
				const pages = r.data.pages || 1;
				if ( pageNum < pages && allItems.length < total ) {
					return fetchPage( pageNum + 1 );
				}
				renderList( allItems );
				return null;
			} );
		}
		fetchPage( 1 ).catch( function () {
			accordion.innerHTML = msgTpl( { message: i18n.error || 'Error' } );
		} );
	}

	const filterApply = listWrap && listWrap.querySelector( '.beruang-filter-apply' );
	if ( filterApply ) filterApply.addEventListener( 'click', loadList );
	const filterReset = listWrap && listWrap.querySelector( '.beruang-filter-reset' );
	if ( filterReset ) {
		filterReset.addEventListener( 'click', function () {
			if ( yearSel ) yearSel.value = accordion.dataset.year || '';
			if ( searchEl ) searchEl.value = '';
			if ( categoryEl ) categoryEl.value = '';
			if ( budgetEl ) budgetEl.value = '';
			if ( walletEl ) walletEl.value = '';
			loadList();
		} );
	}

	accordion.addEventListener( 'click', function ( e ) {
		const head = e.target.closest( '.beruang-accordion-month-head' );
		if ( ! head ) return;
		const month = head.closest( '.beruang-accordion-month' );
		if ( ! month ) return;
		const isOpen = month.classList.contains( 'is-open' );
		month.classList.toggle( 'is-open', ! isOpen );
		head.setAttribute( 'aria-expanded', ! isOpen );
	} );
	accordion.addEventListener( 'keydown', function ( e ) {
		const head = e.target.closest( '.beruang-accordion-month-head' );
		if ( ! head ) return;
		if ( e.key !== 'Enter' && e.key !== ' ' && e.key !== 'Spacebar' ) return;
		e.preventDefault();
		const month = head.closest( '.beruang-accordion-month' );
		if ( ! month ) return;
		const isOpen = month.classList.contains( 'is-open' );
		month.classList.toggle( 'is-open', ! isOpen );
		head.setAttribute( 'aria-expanded', ! isOpen );
	} );

	// Edit transaction modal
	const editModal = document.getElementById( 'beruang-edit-tx-modal' );
	const editForm = document.getElementById( 'beruang-edit-tx-form' );
	if ( editModal && editForm ) {
		document.addEventListener( 'click', function ( e ) {
			const editBtn = e.target.closest( '.beruang-action-edit' );
			if ( ! editBtn ) return;
			const item = editBtn.closest( '.beruang-transaction-item' );
			if ( ! item ) return;
			const id = item.dataset.id;
			if ( ! id ) return;
			request( 'GET', '/transactions/' + id ).then( function ( r ) {
				if ( ! r.success || ! r.data || ! r.data.transaction ) return;
				const t = r.data.transaction;
				const places = getDecimalPlaces();
				const amt = parseFloat( t.amount ) || 0;
				const amtStr =
					places === 0 ? String( Math.round( amt ) ) : amt.toFixed( places );
				const type = t.type === 'income' ? 'income' : 'expense';
				const idEl = editForm.querySelector( '[name="id"]' );
				const dateEl = editForm.querySelector( '[name="date"]' );
				const timeEl = editForm.querySelector( '[name="time"]' );
				const descEl = editForm.querySelector( '[name="description"]' );
				const catEl = editForm.querySelector( '[name="category_id"]' );
				const noteEl = editForm.querySelector( '[name="note"]' );
				const walletEl = editForm.querySelector( '[name="wallet_id"]' );
				const amtEl = editForm.querySelector( '[name="amount"]' );
				const typeEl = editForm.querySelector( '[name="type"]' );
				if ( idEl ) idEl.value = t.id;
				if ( dateEl ) dateEl.value = t.date || '';
				if ( timeEl ) timeEl.value = t.time || '';
				if ( descEl ) descEl.value = t.description || '';
				if ( noteEl ) {
					noteEl.value = t.note || '';
					noteEl.dispatchEvent( new Event( 'input' ) );
				}
				if ( walletEl ) {
					walletEl.value = t.wallet_id ? String( t.wallet_id ) : '';
				}
				if ( catEl ) catEl.value = t.category_id || '0';
				if ( amtEl ) amtEl.value = amtStr;
				if ( typeEl ) typeEl.value = type;
				editForm.querySelectorAll( '.beruang-type-btn' ).forEach( function ( btn ) {
					btn.classList.remove( 'active' );
					if ( btn.dataset.type === type ) btn.classList.add( 'active' );
				} );
				editModal.hidden = false;
			} );
		} );

		document.addEventListener( 'click', function ( e ) {
			const deleteBtn = e.target.closest( '.beruang-action-delete' );
			if ( ! deleteBtn ) return;
			const item = deleteBtn.closest( '.beruang-transaction-item' );
			if ( ! item ) return;
			const id = item.dataset.id;
			if (
				! id ||
				! confirm( i18n.confirm_delete_transaction || 'Delete this transaction?' )
			)
				return;
			request( 'DELETE', '/transactions/' + id ).then( function ( r ) {
				if ( r.success ) loadList();
			} );
		} );

		const editMessage = editForm.querySelector( '.beruang-form-message' );

		const closeEditModal = function () {
			if ( editMessage ) {
				editMessage.textContent = '';
				editMessage.style.color = '';
			}
			editModal.hidden = true;
		};

		const editCancel = document.querySelector( '.beruang-edit-tx-cancel' );
		if ( editCancel ) editCancel.addEventListener( 'click', closeEditModal );
		editModal.addEventListener( 'click', function ( e ) {
			if ( e.target === editModal ) closeEditModal();
		} );
		const editCloseX = editModal.querySelector( '.beruang-modal-close-x' );
		if ( editCloseX ) editCloseX.addEventListener( 'click', function () {
			if ( editMessage ) {
				editMessage.textContent = '';
				editMessage.style.color = '';
			}
		} );

		document.addEventListener( 'beruang-transaction-saved', loadList );
	}

	loadList();
}
