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

	test( 'canvas has initial width and height attributes', async ( { page } ) => {
		await expect( page.locator( '#beruang-graph-canvas' ) ).toHaveAttribute( 'width', '400' );
		await expect( page.locator( '#beruang-graph-canvas' ) ).toHaveAttribute( 'height', '300' );
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
} );
