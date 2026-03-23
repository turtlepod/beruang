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
	// Budget list loading
	// -------------------------------------------------------------------

	test( 'budget list finishes loading and removes the loading indicator', async ( { page } ) => {
		await expect( page.locator( '#beruang-budget-list' ) ).not.toContainText( 'Loading…', {
			timeout: 10_000,
		} );
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

	test( 'creating a yearly budget shows it in the list', async ( { page } ) => {
		const budgetName = `E2E Yearly Budget ${ Date.now() }`;

		await page.locator( '.beruang-budget-add' ).click();
		await page.locator( '#beruang-budget-name' ).fill( budgetName );
		await page.locator( '#beruang-budget-target' ).fill( '1000000' );
		await page.locator( 'select[name="type"]' ).selectOption( 'yearly' );
		await page.locator( '.beruang-modal-save' ).click();

		await expect( page.locator( '#beruang-budget-modal' ) ).toBeHidden( { timeout: 10_000 } );
		await expect( page.locator( '#beruang-budget-list' ) ).toContainText( budgetName, {
			timeout: 10_000,
		} );
	} );

	test( 'edit button opens the budget modal pre-filled with the budget data', async ( { page } ) => {
		const budgetName = `E2E Edit Budget ${ Date.now() }`;

		// Create first.
		await page.locator( '.beruang-budget-add' ).click();
		await page.locator( '#beruang-budget-name' ).fill( budgetName );
		await page.locator( '#beruang-budget-target' ).fill( '200000' );
		await page.locator( '.beruang-modal-save' ).click();
		await expect( page.locator( '#beruang-budget-modal' ) ).toBeHidden( { timeout: 10_000 } );
		await expect( page.locator( '#beruang-budget-list' ) ).toContainText( budgetName, {
			timeout: 10_000,
		} );

		// Click edit.
		await page
			.locator( '.beruang-budget-card', { hasText: budgetName } )
			.locator( '.beruang-action-edit' )
			.click();

		await expect( page.locator( '#beruang-budget-modal' ) ).toBeVisible( { timeout: 5_000 } );
		await expect( page.locator( '#beruang-budget-name' ) ).toHaveValue( budgetName );
	} );

	test( 'deleting a budget removes it from the list', async ( { page } ) => {
		const budgetName = `E2E Delete Budget ${ Date.now() }`;

		await page.locator( '.beruang-budget-add' ).click();
		await page.locator( '#beruang-budget-name' ).fill( budgetName );
		await page.locator( '#beruang-budget-target' ).fill( '300000' );
		await page.locator( '.beruang-modal-save' ).click();
		await expect( page.locator( '#beruang-budget-modal' ) ).toBeHidden( { timeout: 10_000 } );
		await expect( page.locator( '#beruang-budget-list' ) ).toContainText( budgetName, {
			timeout: 10_000,
		} );

		page.on( 'dialog', ( dialog ) => dialog.accept() );
		const deletePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/budgets/' ) &&
				resp.request().method() === 'DELETE'
		);
		await page
			.locator( '.beruang-budget-card', { hasText: budgetName } )
			.locator( '.beruang-action-delete' )
			.click();
		await deletePromise;

		await expect( page.locator( '#beruang-budget-list' ) ).not.toContainText( budgetName, {
			timeout: 10_000,
		} );
	} );

	// -------------------------------------------------------------------
	// Filter: year and month
	// -------------------------------------------------------------------

	test( 'changing the month filter and applying reloads the budget list', async ( { page } ) => {
		const currentMonth = new Date().getMonth() + 1;
		const otherMonth = currentMonth === 12 ? 1 : currentMonth + 1;

		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-filter-month' ).selectOption( String( otherMonth ) );
		const reloadPromise = page.waitForResponse(
			( resp ) => resp.url().includes( '/beruang/v1/budgets' )
		);
		await page.locator( '.beruang-filter-apply' ).click();
		await reloadPromise;

		await expect( page.locator( '#beruang-budget-list' ) ).not.toContainText( 'Loading…', {
			timeout: 10_000,
		} );
	} );

	test( 'filter reset restores current year and month', async ( { page } ) => {
		const year = String( new Date().getFullYear() );
		const month = String( new Date().getMonth() + 1 );

		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-filter-year' ).selectOption(
			String( new Date().getFullYear() - 1 )
		);
		await page.locator( '.beruang-filter-month' ).selectOption( '1' );
		await page.locator( '.beruang-filter-reset' ).click();

		await expect( page.locator( '.beruang-filter-year' ) ).toHaveValue( year );
		await expect( page.locator( '.beruang-filter-month' ) ).toHaveValue( month );
	} );
} );
