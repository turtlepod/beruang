/**
 * Beruang frontend entry point.
 *
 * @package Beruang
 */

'use strict';

import { initForm } from './frontend/form.js';
import { initList } from './frontend/list.js';
import { initGraph } from './frontend/graph.js';
import { initBudget } from './frontend/budget.js';
import { initWallet } from './frontend/wallet.js';

// Modal x close (dismiss without saving).
document.addEventListener( 'click', function ( e ) {
	const btn = e.target.closest( '.beruang-modal-close-x' );
	if ( ! btn ) return;
	const modal = btn.closest( '.beruang-modal' );
	if ( modal ) modal.hidden = true;
} );

/**
 * Move all modals to document.body so they escape the tab strip's
 * will-change:transform / transform containment (which creates a new
 * containing block for position:fixed, breaking modals).
 */
function relocateModals() {
	document.querySelectorAll( '.beruang-modal' ).forEach( function ( modal ) {
		// Restore CSS variable inheritance lost when leaving .beruang container.
		if ( ! modal.classList.contains( 'beruang' ) ) {
			modal.classList.add( 'beruang' );
		}
		document.body.appendChild( modal );
	} );
}

document.addEventListener( 'DOMContentLoaded', function () {
	relocateModals();
	initForm();
	initList();
	initGraph();
	initBudget();
	initWallet();
} );
