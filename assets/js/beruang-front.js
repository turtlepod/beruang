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

// Modal x close (dismiss without saving).
document.addEventListener( 'click', function ( e ) {
	const btn = e.target.closest( '.beruang-modal-close-x' );
	if ( ! btn ) return;
	const modal = btn.closest( '.beruang-modal' );
	if ( modal ) modal.hidden = true;
} );

document.addEventListener( 'DOMContentLoaded', function () {
	initForm();
	initList();
	initGraph();
	initBudget();
} );
