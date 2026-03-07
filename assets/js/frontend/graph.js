/**
 * Beruang graph (Chart.js) visualization.
 *
 * @package Beruang
 */

'use strict';

import { i18n } from './config.js';
import { request } from './utils.js';

export function initGraph() {
	const wrap = document.querySelector( '.beruang-graph-wrapper' );
	const canvas = document.getElementById( 'beruang-graph-canvas' );
	if ( ! canvas || typeof window.Chart === 'undefined' ) return;

	const graphFilters = document.getElementById( 'beruang-graph-filters' );
	const graphFilterBtn = wrap && wrap.querySelector( '.beruang-filter-btn' );
	if ( graphFilterBtn && graphFilters ) {
		graphFilterBtn.addEventListener( 'click', function () {
			graphFilters.hidden = ! graphFilters.hidden;
		} );
	}

	let chart = null;

	function loadGraph() {
		const yearEl = document.querySelector( '.beruang-graph-year' );
		const groupEl = document.querySelector( '.beruang-graph-group' );
		const year = yearEl ? parseInt( yearEl.value, 10 ) : new Date().getFullYear();
		const groupBy = groupEl ? groupEl.value : 'month';
		request( 'GET', '/graph', { year, group_by: groupBy } ).then( function ( r ) {
			if ( ! r.success || ! r.data || ! r.data.data ) return;
			renderChart( r.data.data, groupBy, year );
		} );
	}

	function renderChart( data, groupBy, year ) {
		const ctx = canvas.getContext( '2d' );
		if ( chart ) chart.destroy();

		if ( groupBy === 'category' ) {
			const expenseLabels = [];
			const expenseValues = [];
			const incomeLabels = [];
			const incomeValues = [];
			data.forEach( function ( row ) {
				if ( row.type === 'expense' ) {
					expenseLabels.push( row.label || 'Uncategorized' );
					expenseValues.push( parseFloat( row.total ) );
				} else {
					incomeLabels.push( row.label || 'Uncategorized' );
					incomeValues.push( parseFloat( row.total ) );
				}
			} );
			chart = new window.Chart( ctx, {
				type: 'doughnut',
				data: {
					labels: expenseLabels.length ? expenseLabels : [ i18n.no_data || 'No data' ],
					datasets: [
						{
							data: expenseValues.length ? expenseValues : [ 1 ],
							backgroundColor: [
								'#2271b1',
								'#135e96',
								'#0c4a6e',
								'#72aee6',
								'#3582c4',
							],
						},
					],
				},
				options: { responsive: true, maintainAspectRatio: true },
			} );
		} else {
			const months = [];
			const expenseByMonth = [];
			const incomeByMonth = [];
			for ( let m = 1; m <= 12; m++ ) {
				months.push( m + '/' + year );
				expenseByMonth.push( 0 );
				incomeByMonth.push( 0 );
			}
			data.forEach( function ( row ) {
				const m = parseInt( row.month, 10 ) - 1;
				if ( m >= 0 && m < 12 ) {
					const tot = parseFloat( row.total );
					if ( row.type === 'expense' ) expenseByMonth[ m ] = tot;
					else incomeByMonth[ m ] = tot;
				}
			} );
			chart = new window.Chart( ctx, {
				type: 'bar',
				data: {
					labels: months,
					datasets: [
						{
							label: i18n.expense || 'Expense',
							data: expenseByMonth,
							backgroundColor: '#d63638',
						},
						{
							label: i18n.income || 'Income',
							data: incomeByMonth,
							backgroundColor: '#00a32a',
						},
					],
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					scales: { y: { beginAtZero: true } },
				},
			} );
		}
	}

	const yearEl = document.querySelector( '.beruang-graph-year' );
	const groupEl = document.querySelector( '.beruang-graph-group' );
	if ( yearEl ) yearEl.addEventListener( 'change', loadGraph );
	if ( groupEl ) groupEl.addEventListener( 'change', loadGraph );
	if ( window.Chart ) loadGraph();
	else window.addEventListener( 'load', function () {
		setTimeout( loadGraph, 100 );
	} );
}
