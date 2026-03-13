/**
 * Beruang transaction form, categories modal, and calculator.
 *
 * @package Beruang
 */

'use strict';

import { i18n, getDecimalPlaces, editIcon, deleteIcon } from './config.js';
import { request, beruangTemplate, setFormLoading } from './utils.js';

function setCurrentDateTime( form ) {
	const dateInput = form.querySelector( '[name="date"]' );
	const timeInput = form.querySelector( '[name="time"]' );
	if ( ! dateInput || ! timeInput ) return;
	const now = new Date();
	dateInput.value = now.getFullYear() + '-' +
		String( now.getMonth() + 1 ).padStart( 2, '0' ) + '-' +
		String( now.getDate() ).padStart( 2, '0' );
	timeInput.value = String( now.getHours() ).padStart( 2, '0' ) + ':' +
		String( now.getMinutes() ).padStart( 2, '0' );
}

function syncNoteUi( form ) {
	const noteEl = form.querySelector( '[name="note"]' );
	const noteBtn = form.querySelector( '.beruang-note-btn' );
	if ( ! noteEl ) return;

	const hasNote = ( noteEl.value || '' ).trim().length > 0;
	const addLabel = i18n.add_note || 'Add note';
	const editLabel = i18n.edit_note || 'Edit note';

	if ( noteBtn ) {
		noteBtn.setAttribute( 'title', hasNote ? editLabel : addLabel );
		noteBtn.setAttribute( 'aria-label', hasNote ? editLabel : addLabel );
	}
}

function resetAddForm( form ) {
	const typeField = form.querySelector( '[name="type"]' );
	const message = form.querySelector( '.beruang-form-message' );
	setCurrentDateTime( form );
	form.querySelector( '[name="category_id"]' ).value = '0';
	const walletEl = form.querySelector( '[name="wallet_id"]' );
	if ( walletEl ) {
		walletEl.value = walletEl.dataset.defaultWalletId || walletEl.value || '';
	}
	if ( typeField ) typeField.value = 'expense';
	form.querySelectorAll( '.beruang-type-btn' ).forEach( function ( b ) {
		b.classList.remove( 'active' );
		if ( b.dataset.type === 'expense' ) b.classList.add( 'active' );
	} );
	form.querySelector( '[name="description"]' ).value = '';
	const noteEl = form.querySelector( '[name="note"]' );
	if ( noteEl ) noteEl.value = '';
	syncNoteUi( form );
	form.querySelector( '[name="amount"]' ).value = '';
	if ( message ) {
		message.textContent = i18n.saved || 'Saved.';
		message.style.color = '#00a32a';
	}
}

export function initForm() {
	const optionTpl = beruangTemplate( 'beruang-option' );
	const catItemTpl = beruangTemplate( 'beruang-cat-item' );
	const catEmptyTpl = beruangTemplate( 'beruang-cat-empty' );

	let noteTargetForm = null;
	let noteTargetInput = null;
	const noteModal = document.getElementById( 'beruang-note-modal' );
	const noteModalText = document.getElementById( 'beruang-note-modal-text' );
	const noteSaveBtn = noteModal && noteModal.querySelector( '.beruang-note-save' );
	const noteCancelBtn = noteModal && noteModal.querySelector( '.beruang-note-cancel' );

	function closeNoteModal() {
		noteTargetForm = null;
		noteTargetInput = null;
		if ( noteModal ) noteModal.hidden = true;
	}

	document.addEventListener( 'click', function ( e ) {
		const noteBtn = e.target.closest( '.beruang-note-btn' );
		if ( ! noteBtn || ! noteModal || ! noteModalText ) return;
		const form = noteBtn.closest( '.beruang-transaction-form' );
		if ( ! form ) return;
		const noteInput = form.querySelector( '[name="note"]' );
		if ( ! noteInput ) return;
		noteTargetForm = form;
		noteTargetInput = noteInput;
		noteModalText.value = noteInput.value || '';
		noteModal.hidden = false;
		noteModalText.focus();
	} );

	if ( noteSaveBtn && noteModalText ) {
		noteSaveBtn.addEventListener( 'click', function () {
			if ( noteTargetInput ) {
				noteTargetInput.value = noteModalText.value;
			}
			if ( noteTargetForm ) {
				syncNoteUi( noteTargetForm );
			}
			closeNoteModal();
		} );
	}

	if ( noteCancelBtn ) {
		noteCancelBtn.addEventListener( 'click', closeNoteModal );
	}

	if ( noteModal ) {
		noteModal.addEventListener( 'click', function ( e ) {
			if ( e.target === noteModal ) closeNoteModal();
		} );
	}

	// Categories modal
	const catModal = document.getElementById( 'beruang-categories-modal' );
	const catForm = document.getElementById( 'beruang-category-form' );
	const catList = document.getElementById( 'beruang-categories-list' );
	const catEditId = document.getElementById( 'beruang-cat-edit-id' );
	const catName = document.getElementById( 'beruang-cat-name' );
	const catParent = document.getElementById( 'beruang-cat-parent' );
	const catSubmitBtn = catForm && catForm.querySelector( '.beruang-cat-submit-add' );
	const catCancelBtn = catForm && catForm.querySelector( '.beruang-cat-cancel-edit' );

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

	function buildWalletOptions( wallets ) {
		let opts = optionTpl( {
			value: '',
			label: i18n.no_wallet || 'No Wallet',
		} );
		( wallets || [] ).forEach( function ( w ) {
			opts += optionTpl( {
				value: w.id,
				label: w.name || '',
			} );
		} );
		return opts;
	}

	function refreshWalletSelects() {
		request( 'GET', '/wallets' ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.wallets ) return;
			const wallets = r.data.wallets;
			const defaultWalletId = r.data.default_wallet_id ? String( r.data.default_wallet_id ) : '';
			document.querySelectorAll( '.beruang-transaction-form select[name="wallet_id"]' ).forEach( function ( el ) {
				const currentVal = el.value;
				el.innerHTML = buildWalletOptions( wallets );
				el.dataset.defaultWalletId = defaultWalletId;
				if ( currentVal && el.querySelector( 'option[value="' + currentVal + '"]' ) ) {
					el.value = currentVal;
				} else {
					el.value = defaultWalletId;
				}
			} );
		} );
	}

	function refreshCategoriesInModal( excludeId, selectedParentId ) {
		request( 'GET', '/categories' ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.categories ) return;
			const cats = r.data.categories;
			if ( catParent ) catParent.innerHTML = buildCategoryOptions( cats, excludeId );
			if (
				selectedParentId !== undefined &&
				selectedParentId !== null &&
				catParent
			)
				catParent.value = selectedParentId;
			document.querySelectorAll( '.beruang-transaction-form [name="category_id"]' ).forEach( function ( el ) {
				const currentVal = el.value;
				el.innerHTML = buildMainCategoryOptions( cats );
				if ( currentVal && el.querySelector( 'option[value="' + currentVal + '"]' ) ) {
					el.value = currentVal;
				}
			} );
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
					editIcon,
					deleteIcon,
				} );
			} );
			if ( catList ) {
				catList.innerHTML =
					listHtml ||
					catEmptyTpl( { message: i18n.no_categories || 'No categories yet.' } );
			}
			const loading = document.querySelector( '.beruang-cat-loading' );
			if ( loading ) loading.style.display = 'none';
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		const manageBtn = e.target.closest( '.beruang-manage-categories-btn' );
		if ( ! manageBtn || ! catModal ) return;
		if ( catEditId ) catEditId.value = '';
		if ( catName ) catName.value = '';
		if ( catParent ) catParent.value = '0';
		if ( catSubmitBtn ) {
			catSubmitBtn.textContent = i18n.add_category || 'Add category';
			catSubmitBtn.style.display = '';
		}
		if ( catCancelBtn ) catCancelBtn.style.display = 'none';
		catModal.hidden = false;
		const loading = document.querySelector( '.beruang-cat-loading' );
		if ( loading ) loading.style.display = '';
		if ( catList ) catList.innerHTML = '';
		refreshCategoriesInModal();
	} );

	function closeCatModal() {
		const catMsg = catForm && catForm.querySelector( '.beruang-form-message' );
		if ( catMsg ) {
			catMsg.textContent = '';
			catMsg.style.color = '';
		}
		if ( catModal ) catModal.hidden = true;
	}

	const catModalClose = catModal && catModal.querySelector( '.beruang-categories-modal-close' );
	if ( catModalClose ) catModalClose.addEventListener( 'click', closeCatModal );
	if ( catModal ) {
		catModal.addEventListener( 'click', function ( e ) {
			if ( e.target === catModal ) closeCatModal();
		} );
		const catCloseX = catModal.querySelector( '.beruang-modal-close-x' );
		if ( catCloseX ) catCloseX.addEventListener( 'click', function () {
			const catMsg = catForm && catForm.querySelector( '.beruang-form-message' );
			if ( catMsg ) {
				catMsg.textContent = '';
				catMsg.style.color = '';
			}
		} );
	}

	if ( catForm ) {
		const catMessage = catForm.querySelector( '.beruang-form-message' );
		catForm.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			catMessage.textContent = '';
			const id = catEditId ? catEditId.value : '';
			const name = catName ? catName.value : '';
			const parentId = catParent ? catParent.value || '0' : '0';
			setFormLoading( catForm, true );
			request( 'POST', '/categories', {
				id: id || 0,
				name,
				parent_id: parentId,
			} ).then( function ( r ) {
				if ( r.success ) {
					if ( catEditId ) catEditId.value = '';
					if ( catName ) catName.value = '';
					if ( catParent ) catParent.value = '0';
					if ( catSubmitBtn ) {
						catSubmitBtn.textContent = i18n.add_category || 'Add category';
						catSubmitBtn.style.display = '';
					}
					if ( catCancelBtn ) catCancelBtn.style.display = 'none';
					refreshCategoriesInModal();
				} else {
					catMessage.textContent =
						( r.data && r.data.message ) || i18n.error || 'Error';
					catMessage.style.color = '#d63638';
				}
			} ).catch( function () {
				catMessage.textContent = i18n.error || 'Error';
				catMessage.style.color = '#d63638';
			} ).finally( function () {
				setFormLoading( catForm, false );
			} );
		} );
	}

	if ( catCancelBtn ) {
		catCancelBtn.addEventListener( 'click', function () {
			if ( catEditId ) catEditId.value = '';
			if ( catName ) catName.value = '';
			if ( catParent ) catParent.value = '0';
			if ( catSubmitBtn ) {
				catSubmitBtn.textContent = i18n.add_category || 'Add category';
				catSubmitBtn.style.display = '';
			}
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
		if ( catEditId ) catEditId.value = id;
		if ( catName ) catName.value = name || '';
		if ( catSubmitBtn ) {
			catSubmitBtn.textContent = i18n.update_category || 'Update category';
			catSubmitBtn.style.display = '';
		}
		if ( catCancelBtn ) catCancelBtn.style.display = '';
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

	// Calculator modal – shared; track which form's amount input to insert into
	let calcTargetInput = null;
	const calcModal = document.getElementById( 'beruang-calc-modal' );
	const calcDisplay = calcModal && calcModal.querySelector( '.beruang-calc-display' );
	let calcVal = '0';
	let calcOp = null;
	let calcPrev = null;

	document.addEventListener( 'click', function ( e ) {
		const calcBtn = e.target.closest( '.beruang-calc-btn' );
		if ( ! calcBtn ) return;
		const form = calcBtn.closest( '.beruang-transaction-form' );
		if ( ! form || ! calcModal || ! calcDisplay ) return;
		calcTargetInput = form.querySelector( '[name="amount"]' );
		const initVal = ( calcTargetInput && calcTargetInput.value ) || '0';
		calcVal = initVal;
		calcOp = null;
		calcPrev = null;
		calcDisplay.value = initVal;
		calcModal.hidden = false;
	} );

	if ( calcModal && calcDisplay ) {
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
		const insertCloseBtn = calcModal.querySelector( '.beruang-calc-insert-close' );
		if ( insertCloseBtn ) {
			insertCloseBtn.addEventListener( 'click', function () {
				doEquals();
				const places = getDecimalPlaces();
				const num = parseFloat( calcVal ) || 0;
				if ( calcTargetInput ) {
					calcTargetInput.value =
						places === 0 ? String( Math.round( num ) ) : num.toFixed( places );
					calcTargetInput = null;
				}
				calcModal.hidden = true;
			} );
		}
		calcModal.addEventListener( 'click', function ( e ) {
			if ( e.target === calcModal ) calcModal.hidden = true;
		} );
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
						if ( calcOp && calcPrev !== null ) {
							doEquals();
						}
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

	// Description autocomplete
	function initDescriptionAutocomplete( input ) {
		let debounceTimer = null;
		let currentSuggestions = [];
		let activeIndex = -1;

		const list = document.createElement( 'ul' );
		list.className = 'beruang-desc-suggestions';
		list.hidden = true;

		const wrapper = document.createElement( 'div' );
		wrapper.className = 'beruang-desc-autocomplete-wrap';
		input.parentNode.insertBefore( wrapper, input );
		wrapper.appendChild( input );
		wrapper.appendChild( list );

		function closeSuggestions() {
			list.hidden = true;
			list.innerHTML = '';
			currentSuggestions = [];
			activeIndex = -1;
		}

		function setActive( idx ) {
			const items = list.querySelectorAll( '.beruang-desc-suggestion-item' );
			items.forEach( function ( item ) {
				item.classList.remove( 'is-active' );
			} );
			if ( idx >= 0 && idx < items.length ) {
				items[ idx ].classList.add( 'is-active' );
				activeIndex = idx;
			} else {
				activeIndex = -1;
			}
		}

		function renderSuggestions( suggestions ) {
			list.innerHTML = '';
			if ( ! suggestions.length ) {
				closeSuggestions();
				return;
			}
			suggestions.forEach( function ( text ) {
				const li = document.createElement( 'li' );
				li.className = 'beruang-desc-suggestion-item';
				li.textContent = text;
				li.addEventListener( 'mousedown', function ( e ) {
					e.preventDefault();
					input.value = text;
					closeSuggestions();
				} );
				list.appendChild( li );
			} );
			list.hidden = false;
			activeIndex = -1;
		}

		input.addEventListener( 'input', function () {
			clearTimeout( debounceTimer );
			const val = input.value.trim();
			if ( ! val ) {
				closeSuggestions();
				return;
			}
			debounceTimer = setTimeout( function () {
				request( 'GET', '/descriptions', { search: val } ).then( function ( r ) {
					if ( r.success && r.data && r.data.descriptions ) {
						currentSuggestions = r.data.descriptions;
						renderSuggestions( currentSuggestions );
					}
				} );
			}, 200 );
		} );

		input.addEventListener( 'keydown', function ( e ) {
			if ( list.hidden ) return;
			const items = list.querySelectorAll( '.beruang-desc-suggestion-item' );
			if ( e.key === 'ArrowDown' ) {
				e.preventDefault();
				setActive( Math.min( activeIndex + 1, items.length - 1 ) );
			} else if ( e.key === 'ArrowUp' ) {
				e.preventDefault();
				setActive( Math.max( activeIndex - 1, 0 ) );
			} else if ( e.key === 'Enter' && activeIndex >= 0 ) {
				e.preventDefault();
				input.value = currentSuggestions[ activeIndex ];
				closeSuggestions();
			} else if ( e.key === 'Escape' ) {
				closeSuggestions();
			}
		} );

		input.addEventListener( 'blur', function () {
			setTimeout( closeSuggestions, 150 );
		} );

		window.addEventListener( 'popstate', closeSuggestions );
	}

	// Transaction forms
	const forms = document.querySelectorAll( '.beruang-transaction-form' );
	refreshWalletSelects();
	document.addEventListener( 'beruang-wallets-updated', refreshWalletSelects );
	forms.forEach( function ( form ) {
		const typeField = form.querySelector( '[name="type"]' );
		const message = form.querySelector( '.beruang-form-message' );
		const mode = form.dataset.mode || 'add';
		const isEdit = mode === 'edit';
		const noteEl = form.querySelector( '[name="note"]' );

		if ( ! isEdit ) {
			setCurrentDateTime( form );
		}

		syncNoteUi( form );
		if ( noteEl ) {
			noteEl.addEventListener( 'input', function () {
				syncNoteUi( form );
			} );
		}

		form.querySelectorAll( '.beruang-type-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const t = this.dataset.type;
				form.querySelectorAll( '.beruang-type-btn' ).forEach( function ( b ) {
					b.classList.remove( 'active' );
				} );
				this.classList.add( 'active' );
				if ( typeField ) typeField.value = t;
			} );
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( message ) message.textContent = '';
			setFormLoading( form, true );
			const idEl = form.querySelector( '[name="id"]' );
			const id = idEl ? idEl.value : '';
			const data = {
				date: form.querySelector( '[name="date"]' ).value,
				time: form.querySelector( '[name="time"]' ).value || null,
				description: form.querySelector( '[name="description"]' ).value,
				note: ( form.querySelector( '[name="note"]' ) || { value: '' } ).value,
				wallet_id: ( form.querySelector( '[name="wallet_id"]' ) || { value: '' } ).value || null,
				category_id: form.querySelector( '[name="category_id"]' ).value || 0,
				amount: form.querySelector( '[name="amount"]' ).value,
				type: ( typeField && typeField.value ) || 'expense',
			};
			const method = isEdit && id ? 'PUT' : 'POST';
			const url = isEdit && id ? '/transactions/' + id : '/transactions';
			request( method, url, data ).then( function ( r ) {
				if ( r.success ) {
					if ( isEdit ) {
						const editModal = document.getElementById( 'beruang-edit-tx-modal' );
						if ( editModal ) editModal.hidden = true;
					} else {
						resetAddForm( form );
					}
					document.dispatchEvent( new CustomEvent( 'beruang-transaction-saved' ) );
				} else {
					if ( message ) {
						message.textContent =
							( r.data && r.data.message ) || i18n.error || 'Error';
						message.style.color = '#d63638';
					}
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
	} );

	document.querySelectorAll( '.beruang-transaction-form [name="description"]' ).forEach( initDescriptionAutocomplete );
}
