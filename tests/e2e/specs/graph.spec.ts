import { test, expect } from '../fixtures/pages';

test.describe( '[beruang-graph]', () => {
	test.beforeEach( async ( { page, urls } ) => {
		await page.goto( urls.graph );
		await page.waitForSelector( '.beruang-graph-wrapper' );
	} );

	// -------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------

	test( 'renders the graph wrapper with "Graph" heading', async ( { page } ) => {
		await expect( page.locator( '.beruang-graph-wrapper' ) ).toBeVisible();
		await expect( page.locator( '.beruang-section-title' ) ).toHaveText( 'Graph' );
	} );

	test( 'renders the canvas element', async ( { page } ) => {
		await expect( page.locator( '#beruang-graph-canvas' ) ).toBeVisible();
	} );

	test( 'canvas element exists inside its wrapper', async ( { page } ) => {
		// Chart.js rewrites width/height attributes at runtime; just confirm the canvas is present.
		await expect( page.locator( '.beruang-graph-canvas-wrap canvas' ) ).toHaveCount( 1 );
	} );

	test( 'renders the filter toggle button', async ( { page } ) => {
		await expect( page.locator( '.beruang-filter-btn' ) ).toBeVisible();
	} );

	// -------------------------------------------------------------------
	// Filters panel
	// -------------------------------------------------------------------

	test( 'filter panel is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-graph-filters' ) ).toBeHidden();
	} );

	test( 'filter button toggles the filter panel open', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '#beruang-graph-filters' ) ).toBeVisible();
	} );

	test( 'filter panel has year and grouping selects', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-graph-year' ) ).toBeVisible();
		await expect( page.locator( '.beruang-graph-group' ) ).toBeVisible();
	} );

	test( 'year select is pre-set to the current year', async ( { page } ) => {
		const year = String( new Date().getFullYear() );
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-graph-year' ) ).toHaveValue( year );
	} );

	test( 'grouping select defaults to "By month"', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-graph-group' ) ).toHaveValue( 'month' );
	} );

	test( 'grouping select has "By month" and "By category" options', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-graph-group option[value="month"]' ) ).toHaveText(
			'By month'
		);
		await expect(
			page.locator( '.beruang-graph-group option[value="category"]' )
		).toHaveText( 'By category' );
	} );

	test( 'can switch grouping to "By category"', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-graph-group' ).selectOption( 'category' );
		await expect( page.locator( '.beruang-graph-group' ) ).toHaveValue( 'category' );
	} );

	// -------------------------------------------------------------------
	// API integration
	// -------------------------------------------------------------------

	test( 'graph REST API is called on page load', async ( { page, urls } ) => {
		const graphPromise = page.waitForResponse(
			( resp ) => resp.url().includes( '/beruang/v1/graph' )
		);
		await page.goto( urls.graph );
		const response = await graphPromise;
		expect( response.status() ).toBe( 200 );
	} );

	test( 'switching grouping to "By category" triggers a new graph API call', async ( { page } ) => {
		// Wait for the initial load call to complete first.
		await expect( page.locator( '#beruang-graph-canvas' ) ).toBeVisible();

		const apiPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/graph' ) &&
				resp.url().includes( 'group_by=category' )
		);
		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-graph-group' ).selectOption( 'category' );
		const response = await apiPromise;
		expect( response.status() ).toBe( 200 );
	} );

	test( 'changing the year filter triggers a new graph API call', async ( { page } ) => {
		await expect( page.locator( '#beruang-graph-canvas' ) ).toBeVisible();

		const prevYear = String( new Date().getFullYear() - 1 );
		const apiPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/graph' ) &&
				resp.url().includes( `year=${ prevYear }` )
		);
		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-graph-year' ).selectOption( prevYear );
		const response = await apiPromise;
		expect( response.status() ).toBe( 200 );
	} );

	test( 'can switch grouping back to "By month"', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-graph-group' ).selectOption( 'category' );
		await page.locator( '.beruang-graph-group' ).selectOption( 'month' );
		await expect( page.locator( '.beruang-graph-group' ) ).toHaveValue( 'month' );
	} );
} );
