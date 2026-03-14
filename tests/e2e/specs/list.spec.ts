import { test, expect } from '../fixtures/pages';

test.describe( '[beruang-list]', () => {
	test.beforeEach( async ( { page, urls } ) => {
		await page.goto( urls.list );
		await page.waitForSelector( '.beruang-list-wrapper' );
	} );

	// -------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------

	test( 'renders the list wrapper with "Transactions" heading', async ( { page } ) => {
		await expect( page.locator( '.beruang-list-wrapper' ) ).toBeVisible();
		await expect( page.locator( '.beruang-section-title' ) ).toHaveText( 'Transactions' );
	} );

	test( 'renders the filter toggle button', async ( { page } ) => {
		await expect( page.locator( '.beruang-filter-btn' ) ).toBeVisible();
	} );

	test( 'renders the list accordion container', async ( { page } ) => {
		await expect( page.locator( '#beruang-list-accordion' ) ).toBeVisible();
	} );

	test( 'accordion container has a data-year attribute matching the current year', async ( { page } ) => {
		const year = String( new Date().getFullYear() );
		await expect( page.locator( '#beruang-list-accordion' ) ).toHaveAttribute(
			'data-year',
			year
		);
	} );

	// -------------------------------------------------------------------
	// Filters panel
	// -------------------------------------------------------------------

	test( 'filter panel is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-list-filters' ) ).toBeHidden();
	} );

	test( 'filter button toggles the filter panel open', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '#beruang-list-filters' ) ).toBeVisible();
	} );

	test( 'filter panel has year, search, category, and budget controls', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-filter-year' ) ).toBeVisible();
		await expect( page.locator( '.beruang-filter-search' ) ).toBeVisible();
		await expect( page.locator( '.beruang-filter-category' ) ).toBeVisible();
		await expect( page.locator( '.beruang-filter-budget' ) ).toBeVisible();
	} );

	test( 'year filter is pre-selected to the current year', async ( { page } ) => {
		const year = String( new Date().getFullYear() );
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-filter-year' ) ).toHaveValue( year );
	} );

	test( 'category filter contains "All categories" and "Uncategorized" options', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect(
			page.locator( '.beruang-filter-category option[value=""]' )
		).toHaveText( 'All categories' );
		await expect(
			page.locator( '.beruang-filter-category option[value="0"]' )
		).toHaveText( 'Uncategorized' );
	} );

	test( 'budget filter contains "All budgets" option', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect(
			page.locator( '.beruang-filter-budget option[value=""]' )
		).toHaveText( 'All budgets' );
	} );

	test( 'filter panel has Apply and Reset buttons', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-filter-apply' ) ).toBeVisible();
		await expect( page.locator( '.beruang-filter-reset' ) ).toBeVisible();
	} );

	// -------------------------------------------------------------------
	// Edit modal
	// -------------------------------------------------------------------

	test( 'edit-transaction modal is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeHidden();
	} );

	// -------------------------------------------------------------------
	// Transactions load
	// -------------------------------------------------------------------

	test( 'loading indicator disappears after transactions are fetched', async ( { page } ) => {
		// Wait until the "Loading…" paragraph is gone (JS replaced it with data or empty state).
		await expect( page.locator( '#beruang-list-accordion .beruang-loading' ) ).toBeHidden( {
			timeout: 10_000,
		} );
	} );

	// -------------------------------------------------------------------
	// Integration: transaction added via form appears in the list
	// -------------------------------------------------------------------

	test( 'a transaction submitted on the form page appears in the list', async ( { page, urls } ) => {
		const description = `E2E list test ${ Date.now() }`;

		// Submit a transaction on the form page.
		await page.goto( urls.form );
		await page.locator( '#beruang-description' ).fill( description );
		await page.locator( '#beruang-amount' ).fill( '5000' );
		await page.locator( '.beruang-submit' ).click();
		await expect( page.locator( '#beruang-description' ) ).toHaveValue( '', {
			timeout: 10_000,
		} );

		// Navigate to the list page and verify the transaction is visible.
		await page.goto( urls.list );
		await expect( page.locator( '#beruang-list-accordion .beruang-loading' ) ).toBeHidden( {
			timeout: 10_000,
		} );
		await expect( page.locator( '#beruang-list-accordion' ) ).toContainText( description );
	} );
} );
