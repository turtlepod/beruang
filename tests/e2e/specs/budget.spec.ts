import { test, expect } from '../fixtures/pages';

test.describe( '[beruang-budget]', () => {
	test.beforeEach( async ( { page, urls } ) => {
		await page.goto( urls.budget );
		await page.waitForSelector( '.beruang-budget-wrapper' );
	} );

	// -------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------

	test( 'renders the budget wrapper with "Budgets" heading', async ( { page } ) => {
		await expect( page.locator( '.beruang-budget-wrapper' ) ).toBeVisible();
		await expect( page.locator( '.beruang-section-title' ) ).toHaveText( 'Budgets' );
	} );

	test( 'renders the Add budget button', async ( { page } ) => {
		await expect( page.locator( '.beruang-budget-add' ) ).toBeVisible();
	} );

	test( 'renders the filter toggle button', async ( { page } ) => {
		await expect( page.locator( '.beruang-filter-btn' ) ).toBeVisible();
	} );

	test( 'renders the budget list container with current year/month data attributes', async ( { page } ) => {
		const year = String( new Date().getFullYear() );
		const month = String( new Date().getMonth() + 1 );
		const list = page.locator( '#beruang-budget-list' );
		await expect( list ).toBeVisible();
		await expect( list ).toHaveAttribute( 'data-year', year );
		await expect( list ).toHaveAttribute( 'data-month', month );
	} );

	// -------------------------------------------------------------------
	// Filters panel
	// -------------------------------------------------------------------

	test( 'filter panel is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-budget-filters' ) ).toBeHidden();
	} );

	test( 'filter button toggles the filter panel open', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '#beruang-budget-filters' ) ).toBeVisible();
	} );

	test( 'filter panel has year, month, Apply, and Reset controls', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect( page.locator( '.beruang-filter-year' ) ).toBeVisible();
		await expect( page.locator( '.beruang-filter-month' ) ).toBeVisible();
		await expect( page.locator( '.beruang-filter-apply' ) ).toBeVisible();
		await expect( page.locator( '.beruang-filter-reset' ) ).toBeVisible();
	} );

	test( 'month select contains all 12 months', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		const options = page.locator( '.beruang-filter-month option' );
		await expect( options ).toHaveCount( 12 );
	} );

	// -------------------------------------------------------------------
	// Budget modal
	// -------------------------------------------------------------------

	test( 'budget modal is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-budget-modal' ) ).toBeHidden();
	} );

	test( 'Add budget button opens the modal', async ( { page } ) => {
		await page.locator( '.beruang-budget-add' ).click();
		await expect( page.locator( '#beruang-budget-modal' ) ).toBeVisible();
	} );

	test( 'modal has name, target, and type fields', async ( { page } ) => {
		await page.locator( '.beruang-budget-add' ).click();
		await expect( page.locator( '#beruang-budget-name' ) ).toBeVisible();
		await expect( page.locator( '#beruang-budget-target' ) ).toBeVisible();
		await expect( page.locator( 'select[name="type"]' ) ).toBeVisible();
	} );

	test( 'type select has "Monthly" and "Yearly" options', async ( { page } ) => {
		await page.locator( '.beruang-budget-add' ).click();
		await expect( page.locator( 'select[name="type"] option[value="monthly"]' ) ).toHaveText(
			'Monthly'
		);
		await expect( page.locator( 'select[name="type"] option[value="yearly"]' ) ).toHaveText(
			'Yearly'
		);
	} );

	test( 'modal × button closes it', async ( { page } ) => {
		await page.locator( '.beruang-budget-add' ).click();
		await page.locator( '#beruang-budget-modal .beruang-modal-close-x' ).click();
		await expect( page.locator( '#beruang-budget-modal' ) ).toBeHidden();
	} );

	test( 'Cancel button closes the modal', async ( { page } ) => {
		await page.locator( '.beruang-budget-add' ).click();
		await page.locator( '.beruang-budget-modal-close' ).click();
		await expect( page.locator( '#beruang-budget-modal' ) ).toBeHidden();
	} );

	// -------------------------------------------------------------------
	// Integration: create a budget and verify it appears in the list
	// -------------------------------------------------------------------

	test( 'creating a budget shows it in the budget list', async ( { page } ) => {
		const budgetName = `E2E Budget ${ Date.now() }`;

		await page.locator( '.beruang-budget-add' ).click();
		await page.locator( '#beruang-budget-name' ).fill( budgetName );
		await page.locator( '#beruang-budget-target' ).fill( '500000' );
		await page.locator( '.beruang-modal-save' ).click();

		// Modal should close and the new budget appear in the list.
		await expect( page.locator( '#beruang-budget-modal' ) ).toBeHidden( {
			timeout: 10_000,
		} );
		await expect( page.locator( '#beruang-budget-list' ) ).toContainText( budgetName, {
			timeout: 10_000,
		} );
	} );
} );
