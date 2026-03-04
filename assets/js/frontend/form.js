/**
 * Beruang transaction form, categories modal, and calculator.
 *
 * @package Beruang
 */

'use strict';

import { i18n, getDecimalPlaces } from './config.js';
import { request, beruangTemplate } from './utils.js';

export function initForm() {
	const form = document.getElementById( 'beruang-transaction-form' );
	if ( ! form ) return;

	const typeField = document.getElementById( 'beruang-type' );
	const message = form.querySelector( '.beruang-form-message' );
	const dateInput = document.getElementById( 'beruang-date' );
	const timeInput = document.getElementById( 'beruang-time' );

	function setCurrentDateTime() {
		const now = new Date();
		const y = now.getFullYear();
		const m = String( now.getMonth() + 1 ).padStart( 2, '0' );
		const d = String( now.getDate() ).padStart( 2, '0' );
		const h = String( now.getHours() ).padStart( 2, '0' );
		const i = String( now.getMinutes() ).padStart( 2, '0' );
		if ( dateInput ) dateInput.value = y + '-' + m + '-' + d;
		if ( timeInput ) timeInput.value = h + ':' + i;
	}

	setCurrentDateTime();

	form.querySelectorAll( '.beruang-type-btn' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			const t = this.dataset.type;
			form
				.querySelectorAll( '.beruang-type-btn' )
				.forEach( function ( b ) { b.classList.remove( 'active' ); } );
			this.classList.add( 'active' );
			typeField.value = t;
		} );
	} );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		message.textContent = '';
		const data = {
			date: form.querySelector( '[name="date"]' ).value,
			time: form.querySelector( '[name="time"]' ).value || null,
			description: form.querySelector( '[name="description"]' ).value,
			category_id: form.querySelector( '[name="category_id"]' ).value || 0,
			amount: form.querySelector( '[name="amount"]' ).value,
			type: typeField.value,
		};
		request( 'POST', '/transactions', data ).then( function ( r ) {
			if ( r.success ) {
				message.textContent = i18n.saved || 'Saved.';
				message.style.color = '#00a32a';
				setCurrentDateTime();
				form.querySelector( '[name="category_id"]' ).value = '0';
				typeField.value = 'expense';
				form
					.querySelectorAll( '.beruang-type-btn' )
					.forEach( function ( b ) { b.classList.remove( 'active' ); } );
				const expenseBtn = form.querySelector(
					'.beruang-type-btn[data-type="expense"]'
				);
				if ( expenseBtn ) expenseBtn.classList.add( 'active' );
				form.querySelector( '[name="description"]' ).value = '';
				form.querySelector( '[name="amount"]' ).value = '';
			} else {
				message.textContent =
					( r.data && r.data.message ) || i18n.error || 'Error';
				message.style.color = '#d63638';
			}
		} ).catch( function () {
			message.textContent = i18n.error || 'Error';
			message.style.color = '#d63638';
		} );
	} );

	// Categories modal
	const catModal = document.getElementById( 'beruang-categories-modal' );
	const catForm = document.getElementById( 'beruang-category-form' );
	const catList = document.getElementById( 'beruang-categories-list' );
	const catEditId = document.getElementById( 'beruang-cat-edit-id' );
	const catName = document.getElementById( 'beruang-cat-name' );
	const catParent = document.getElementById( 'beruang-cat-parent' );
	const catSubmitBtn = catForm && catForm.querySelector( '.beruang-cat-submit-add' );
	const catCancelBtn = catForm && catForm.querySelector( '.beruang-cat-cancel-edit' );
	const mainCategorySelect = document.getElementById( 'beruang-category' );
	const optionTpl = beruangTemplate( 'beruang-option' );
	const catItemTpl = beruangTemplate( 'beruang-cat-item' );
	const catEmptyTpl = beruangTemplate( 'beruang-cat-empty' );

	function buildCategoryOptions( categories, excludeId ) {
		let opts = optionTpl( { value: '0', label: '—' } );
		( categories || [] ).forEach( function ( c ) {
			if ( excludeId && parseInt( c.id, 10 ) === parseInt( excludeId, 10 ) )
				return;
			const depth = parseInt( c.depth, 10 ) || 0;
			const indent = new Array( depth + 1 ).join( '— ' );
			opts += optionTpl( { value: c.id, label: indent + ( c.name || '' ) } );
		} );
		return opts;
	}

	function buildMainCategoryOptions( categories ) {
		let opts = optionTpl( {
			value: '0',
			label: i18n.uncategorized || 'Uncategorized',
		} );
		( categories || [] ).forEach( function ( c ) {
			const depth = parseInt( c.depth, 10 ) || 0;
			const indent = new Array( depth + 1 ).join( '— ' );
			opts += optionTpl( { value: c.id, label: indent + ( c.name || '' ) } );
		} );
		return opts;
	}

	function refreshCategoriesInModal( excludeId, selectedParentId ) {
		request( 'GET', '/categories' ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.categories ) return;
			const cats = r.data.categories;
			catParent.innerHTML = buildCategoryOptions( cats, excludeId );
			if (
				selectedParentId !== undefined &&
				selectedParentId !== null
			)
				catParent.value = selectedParentId;
			mainCategorySelect.innerHTML = buildMainCategoryOptions( cats );
			let listHtml = '';
			cats.forEach( function ( c ) {
				const depth = parseInt( c.depth, 10 ) || 0;
				const indent = new Array( depth + 1 ).join( '— ' );
				listHtml += catItemTpl( {
					id: c.id,
					name: c.name || '',
					parent: c.parent_id || 0,
					displayName: indent + ( c.name || '' ),
					editLabel: i18n.edit || 'Edit',
					deleteLabel: i18n.delete || 'Delete',
				} );
			} );
			catList.innerHTML =
				listHtml ||
				catEmptyTpl( { message: i18n.no_categories || 'No categories yet.' } );
			const loading = document.querySelector( '.beruang-cat-loading' );
			if ( loading ) loading.style.display = 'none';
		} );
	}

	const manageBtn = document.querySelector( '.beruang-manage-categories-btn' );
	if ( manageBtn ) {
		manageBtn.addEventListener( 'click', function () {
			catEditId.value = '';
			catName.value = '';
			catParent.value = '0';
			catSubmitBtn.textContent = i18n.add_category || 'Add category';
			catSubmitBtn.style.display = '';
			catCancelBtn.style.display = 'none';
			catModal.hidden = false;
			const loading = document.querySelector( '.beruang-cat-loading' );
			if ( loading ) loading.style.display = '';
			catList.innerHTML = '';
			refreshCategoriesInModal();
		} );
	}

	const catModalClose = catModal && catModal.querySelector( '.beruang-categories-modal-close' );
	if ( catModalClose ) {
		catModalClose.addEventListener( 'click', function () {
			catModal.hidden = true;
		} );
	}
	if ( catModal ) {
		catModal.addEventListener( 'click', function ( e ) {
			if ( e.target === catModal ) catModal.hidden = true;
		} );
	}

	if ( catForm ) {
		catForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			const id = catEditId.value;
			const name = catName.value;
			const parentId = catParent.value || '0';
			request( 'POST', '/categories', {
				id: id || 0,
				name,
				parent_id: parentId,
			} ).then( function ( r ) {
				if ( r.success ) {
					catEditId.value = '';
					catName.value = '';
					catParent.value = '0';
					catSubmitBtn.textContent = i18n.add_category || 'Add category';
					catCancelBtn.style.display = 'none';
					refreshCategoriesInModal();
				}
			} );
		} );
	}

	if ( catCancelBtn ) {
		catCancelBtn.addEventListener( 'click', function () {
			catEditId.value = '';
			catName.value = '';
			catParent.value = '0';
			catSubmitBtn.textContent = i18n.add_category || 'Add category';
			catSubmitBtn.style.display = '';
			catCancelBtn.style.display = 'none';
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		const editBtn = e.target.closest( '.beruang-action-edit' );
		if ( ! editBtn ) return;
		const li = editBtn.closest( '.beruang-cat-item' );
		if ( ! li || ! catModal ) return;
		const id = li.dataset.id;
		const name = li.dataset.name;
		const parent = li.dataset.parent;
		catEditId.value = id;
		catName.value = name || '';
		catSubmitBtn.textContent = i18n.update_category || 'Update category';
		catSubmitBtn.style.display = '';
		catCancelBtn.style.display = '';
		refreshCategoriesInModal( id, parent || '0' );
	} );

	document.addEventListener( 'click', function ( e ) {
		const deleteBtn = e.target.closest( '.beruang-action-delete' );
		if ( ! deleteBtn ) return;
		const li = deleteBtn.closest( '.beruang-cat-item' );
		if ( ! li ) return;
		const id = li.dataset.id;
		if ( ! id || ! confirm( i18n.confirm_delete_category || 'Delete this category?' ) )
			return;
		request( 'DELETE', '/categories/' + id ).then( function ( r ) {
			if ( r.success ) refreshCategoriesInModal();
		} );
	} );

	// Calculator modal
	const calcBtn = form.querySelector( '.beruang-calc-btn' );
	const calcModal = document.getElementById( 'beruang-calc-modal' );
	const calcDisplay = calcModal && calcModal.querySelector( '.beruang-calc-display' );
	const amountInput = form.querySelector( '#beruang-amount' );
	if ( calcModal && calcBtn && calcDisplay && amountInput ) {
		calcBtn.addEventListener( 'click', function () {
			calcDisplay.value = amountInput.value || '0';
			calcModal.hidden = false;
		} );
		const insertCloseBtn = calcModal.querySelector( '.beruang-calc-insert-close' );
		if ( insertCloseBtn ) {
			insertCloseBtn.addEventListener( 'click', function () {
				doEquals();
				const places = getDecimalPlaces();
				const num = parseFloat( calcVal ) || 0;
				amountInput.value =
					places === 0 ? String( Math.round( num ) ) : num.toFixed( places );
				calcModal.hidden = true;
			} );
		}
		calcModal.addEventListener( 'click', function ( e ) {
			if ( e.target === calcModal ) calcModal.hidden = true;
		} );
		let calcVal = '0';
		let calcOp = null;
		let calcPrev = null;
		const updateDisplay = function () {
			if ( calcOp && calcPrev !== null ) {
				const second = calcVal === '0' ? '' : calcVal;
				calcDisplay.value =
					calcPrev + ' ' + calcOp + ( second ? ' ' + second : '' );
			} else {
				calcDisplay.value = calcVal;
			}
		};
		const doClear = function () {
			calcVal = '0';
			calcOp = null;
			calcPrev = null;
			updateDisplay();
		};
		const doEquals = function () {
			if ( calcOp && calcPrev !== null ) {
				const a = parseFloat( calcPrev );
				const bNum = parseFloat( calcVal );
				const op =
					calcOp === '\u00f7' ? '/' : calcOp === '\u00d7' ? '*' : calcOp;
				if ( op === '+' ) calcVal = String( a + bNum );
				else if ( op === '-' ) calcVal = String( a - bNum );
				else if ( op === '*' ) calcVal = String( a * bNum );
				else if ( op === '/' )
					calcVal = bNum !== 0 ? String( a / bNum ) : '0';
				calcOp = null;
				calcPrev = null;
			}
			updateDisplay();
		};
		const btns = [
			[ '7', '8', '9', '\u00f7' ],
			[ '4', '5', '6', '\u00d7' ],
			[ '1', '2', '3', '-' ],
			[ '0', '000', '.', '+' ],
		];
		const container = calcModal.querySelector( '.beruang-calc-buttons' );
		container.innerHTML = '';
		btns.forEach( function ( row ) {
			row.forEach( function ( key ) {
				const isOp =
					key === '+' ||
					key === '-' ||
					key === '*' ||
					key === '/' ||
					key === '\u00f7' ||
					key === '\u00d7';
				const b = document.createElement( 'button' );
				b.type = 'button';
				b.textContent = key;
				if ( isOp ) b.classList.add( 'beruang-calc-op' );
				b.addEventListener( 'click', function () {
					if (
						key === '+' ||
						key === '-' ||
						key === '*' ||
						key === '/' ||
						key === '\u00f7' ||
						key === '\u00d7'
					) {
						calcPrev = calcVal;
						calcOp = key;
						calcVal = '0';
						updateDisplay();
					} else {
						if ( calcVal === '0' && key !== '.' ) calcVal = key;
						else calcVal += key;
						updateDisplay();
					}
				} );
				container.appendChild( b );
			} );
		} );
		const clearBtn = calcModal.querySelector( '.beruang-calc-clear' );
		if ( clearBtn ) clearBtn.addEventListener( 'click', doClear );
		const equalsBtn = calcModal.querySelector( '.beruang-calc-equals' );
		if ( equalsBtn ) equalsBtn.addEventListener( 'click', doEquals );
	}
}
