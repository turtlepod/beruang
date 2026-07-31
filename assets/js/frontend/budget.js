/**
 * Beruang budget CRUD and list.
 *
 * @package Beruang
 */

'use strict';

import { i18n, editIcon, deleteIcon } from './config.js';
import { request, beruangTemplate, formatNum, formatAmountForInput } from './utils.js';

export function initBudget() {
	const list = document.getElementById( 'beruang-budget-list' );
	const modal = document.getElementById( 'beruang-budget-modal' );
	const form = document.getElementById( 'beruang-budget-form' );
	if ( ! list ) return;

	const listeners = [];
	function on( target, event, handler, options ) {
		target.addEventListener( event, handler, options );
		listeners.push( { target, event, handler } );
	}

	const budgetWrap = list.closest( '.beruang-budget-wrapper' );
	const filters = budgetWrap && budgetWrap.querySelector( '#beruang-budget-filters' );
	const filterBtn =
		budgetWrap && budgetWrap.querySelector( '.beruang-budget-header .beruang-filter-btn' );

	if ( filterBtn && filters ) {
		on( filterBtn, 'click', function () {
			filters.hidden = ! filters.hidden;
		} );
	}

	const msgTpl = beruangTemplate( 'beruang-message' );
	const budgetCardTpl = beruangTemplate( 'beruang-budget-card' );

	function onDeleteClick( e ) {
		const deleteBtn = e.target.closest( '.beruang-action-delete' );
		if ( ! deleteBtn ) return;
		const card = deleteBtn.closest( '.beruang-budget-card' );
		if ( ! card ) return;
		const id = deleteBtn.dataset.id;
		if ( ! id || ! confirm( i18n.confirm_delete || 'Delete this budget?' ) ) return;
		request( 'DELETE', '/budgets/' + id ).then( function ( res ) {
			if ( res.success ) loadBudgets();
		} );
	}

	function onEditClick( e ) {
		const editBtn = e.target.closest( '.beruang-action-edit' );
		if ( ! editBtn ) return;
		const card = editBtn.closest( '.beruang-budget-card' );
		if ( ! card ) return;
		const id = editBtn.dataset.id;
		if ( ! id ) return;
		request( 'GET', '/budgets/' + id ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.budget ) return;
			const b = r.data.budget;
			form.querySelector( '[name="id"]' ).value = b.id;
			form.querySelector( '[name="name"]' ).value = b.name || '';
			form.querySelector( '[name="target_amount"]' ).value = formatAmountForInput( b.target_amount ) || '';
			form.querySelector( '[name="type"]' ).value =
				b.type === 'yearly' ? 'yearly' : 'monthly';
			form.querySelectorAll( '[name="category_ids[]"]' ).forEach( function ( cb ) {
				cb.checked = false;
			} );
			( b.category_ids || [] ).forEach( function ( cid ) {
				const cb = form.querySelector(
					'[name="category_ids[]"][value="' + cid + '"]'
				);
				if ( cb ) cb.checked = true;
			} );
			modal.hidden = false;
		} );
	}

	on( document, 'click', onDeleteClick );
	on( document, 'click', onEditClick );

	function loadBudgets() {
		const yearSel = budgetWrap && budgetWrap.querySelector( '.beruang-filter-year' );
		const monthSel = budgetWrap && budgetWrap.querySelector( '.beruang-filter-month' );
		const year = yearSel
			? parseInt( yearSel.value, 10 )
			: parseInt( list.dataset.year, 10 );
		const month = monthSel
			? parseInt( monthSel.value, 10 )
			: parseInt( list.dataset.month, 10 );
		list.innerHTML = msgTpl( { message: i18n.loading || 'Loading…' } );
		request( 'GET', '/budgets', { year, month } )
			.then( function ( r ) {
				if ( ! r.success || ! r.data || ! r.data.budgets ) {
					list.innerHTML = msgTpl( { message: i18n.error || 'Error' } );
					return;
				}
				const budgets = r.data.budgets;
				let html = '';
				budgets.forEach( function ( b ) {
					const pct = Math.round( parseFloat( b.progress ) || 0 );
					const remaining = parseFloat( b.target_amount ) - parseFloat( b.spent );
					const over = remaining < 0;
					const warning = ! over && pct >= 80;
					html += budgetCardTpl( {
						id: b.id,
						name: b.name,
						typeLabel:
							b.type === 'yearly'
								? ( i18n.yearly || 'Yearly' )
								: ( i18n.monthly || 'Monthly' ),
						progressWidth: Math.min( pct, 100 ),
						progressClass: over ? 'over' : ( warning ? 'warning' : '' ),
						spentFormatted: formatNum( b.spent ),
						targetFormatted: formatNum( b.target_amount ),
						remainingFormatted: formatNum( Math.abs( remaining ) ),
						remainingLabel: over ? ( i18n.over_budget || 'Over' ) : ( i18n.remaining || 'Remaining' ),
						remainingClass: over ? 'beruang-budget-over' : '',
						pct,
						editLabel: i18n.edit || 'Edit',
						deleteLabel: i18n.delete || 'Delete',
						editIcon,
						deleteIcon,
					} );
				} );
				if ( ! budgets.length )
					html = msgTpl( { message: i18n.no_budgets || 'No budgets.' } );
				list.innerHTML = html;
			} )
			.catch( function () {
				list.innerHTML = msgTpl( { message: i18n.error || 'Error' } );
			} );
	}

	if ( budgetWrap ) {
		on( budgetWrap, 'click', function ( e ) {
			if ( e.target.closest( '.beruang-filter-apply' ) ) loadBudgets();
		} );
		on( budgetWrap, 'click', function ( e ) {
			if ( ! e.target.closest( '.beruang-filter-reset' ) ) return;
			const yearSel = budgetWrap.querySelector( '.beruang-filter-year' );
			const monthSel = budgetWrap.querySelector( '.beruang-filter-month' );
			if ( yearSel ) yearSel.value = list.dataset.year || '';
			if ( monthSel ) monthSel.value = list.dataset.month || '';
			loadBudgets();
		} );
	}

	const budgetAdd = budgetWrap && budgetWrap.querySelector( '.beruang-budget-add' );
	if ( budgetAdd ) {
		on( budgetAdd, 'click', function () {
			form.querySelector( '[name="id"]' ).value = '';
			form.querySelector( '[name="name"]' ).value = '';
			form.querySelector( '[name="target_amount"]' ).value = '';
			form.querySelector( '[name="type"]' ).value = 'monthly';
			form.querySelectorAll( '[name="category_ids[]"]' ).forEach( function ( cb ) {
				cb.checked = false;
			} );
			modal.hidden = false;
		} );
	}

	if ( modal ) {
		on( modal, 'click', function ( e ) {
			if (
				e.target === modal ||
				e.target.classList.contains( 'beruang-budget-modal-close' )
			)
				modal.hidden = true;
		} );
	}

	on( form, 'submit', function ( e ) {
		e.preventDefault();
		const id = form.querySelector( '[name="id"]' ).value;
		const name = form.querySelector( '[name="name"]' ).value;
		const target = form.querySelector( '[name="target_amount"]' ).value;
		const type = form.querySelector( '[name="type"]' ).value;
		const catIds = [];
		form
			.querySelectorAll( '[name="category_ids[]"]:checked' )
			.forEach( function ( cb ) {
				catIds.push( cb.value );
			} );
		request( 'POST', '/budgets', {
			id: id || 0,
			name,
			target_amount: target,
			type,
			category_ids: catIds,
		} ).then( function ( r ) {
			if ( r.success ) {
				modal.hidden = true;
				loadBudgets();
			}
		} );
	} );

	loadBudgets();
	on( document, 'beruang-transaction-saved', loadBudgets );

	return function destroy() {
		while ( listeners.length ) {
			const l = listeners.pop();
			l.target.removeEventListener( l.event, l.handler );
		}
	};
}
