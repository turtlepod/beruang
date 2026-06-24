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

	test( 'filter panel has wallet filter with "All wallets" and "No wallet" options', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await expect(
			page.locator( '.beruang-filter-wallet option[value=""]' )
		).toHaveText( 'All wallets' );
		await expect(
			page.locator( '.beruang-filter-wallet option[value="0"]' )
		).toHaveText( 'No wallet' );
	} );

	test( 'filter reset clears the search input', async ( { page } ) => {
		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-filter-search' ).fill( 'some search text' );
		await page.locator( '.beruang-filter-reset' ).click();
		await expect( page.locator( '.beruang-filter-search' ) ).toHaveValue( '' );
	} );

	test( 'filter reset restores year to current year', async ( { page } ) => {
		const year = String( new Date().getFullYear() );
		const prevYear = String( new Date().getFullYear() - 1 );
		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-filter-year' ).selectOption( prevYear );
		await page.locator( '.beruang-filter-reset' ).click();
		await expect( page.locator( '.beruang-filter-year' ) ).toHaveValue( year );
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

	test( 'loading indicator text changes after transactions are fetched', async ( { page } ) => {
		// The JS replaces "Loading…" with actual rows or "No transactions." once the REST call returns.
		await expect( page.locator( '#beruang-list-accordion' ) ).not.toContainText( 'Loading…', {
			timeout: 10_000,
		} );
	} );

	// -------------------------------------------------------------------
	// Search filter
	// -------------------------------------------------------------------

	test( 'search filter shows only matching transactions', async ( { page, urls } ) => {
		const description = `E2E search ${ Date.now() }`;

		// Create a transaction first.
		await page.goto( urls.form );
		const postPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-description' ).fill( description );
		await page.locator( '#beruang-amount' ).fill( '1000' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();
		await postPromise;

		// Navigate to list, search, apply.
		await page.goto( urls.list );
		await expect( page.locator( '#beruang-list-accordion' ) ).not.toContainText( 'Loading…', {
			timeout: 10_000,
		} );
		await page.locator( '.beruang-filter-btn' ).click();
		await page.locator( '.beruang-filter-search' ).fill( description );
		const searchPromise = page.waitForResponse(
			( resp ) => resp.url().includes( '/beruang/v1/transactions' )
		);
		await page.locator( '.beruang-filter-apply' ).click();
		await searchPromise;

		await expect( page.locator( '#beruang-list-accordion' ) ).toContainText( description, {
			timeout: 10_000,
		} );
	} );

	// -------------------------------------------------------------------
	// Edit transaction modal
	// -------------------------------------------------------------------

	test( 'clicking edit on a transaction opens the edit modal pre-filled', async ( { page, urls } ) => {
		const description = `E2E edit modal ${ Date.now() }`;

		// Create via form.
		await page.goto( urls.form );
		const postPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-description' ).fill( description );
		await page.locator( '#beruang-amount' ).fill( '2000' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();
		await postPromise;

		// Open list and find the row.
		await page.goto( urls.list );
		await expect( page.locator( '#beruang-list-accordion' ) ).toContainText( description, {
			timeout: 10_000,
		} );

		// Click the edit button for that transaction row.
		await page
			.locator( '.beruang-transaction-item', { hasText: description } )
			.locator( '.beruang-action-edit' )
			.click();

		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeVisible( { timeout: 5_000 } );
		await expect( page.locator( '#beruang-edit-tx-description' ) ).toHaveValue( description );
	} );

	test( 'saving an edited transaction updates it in the list', async ( { page, urls } ) => {
		const original = `E2E orig ${ Date.now() }`;
		const updated = `E2E updated ${ Date.now() }`;

		// Create via form.
		await page.goto( urls.form );
		const postPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-description' ).fill( original );
		await page.locator( '#beruang-amount' ).fill( '3000' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();
		await postPromise;

		// Open list, open edit modal.
		await page.goto( urls.list );
		await expect( page.locator( '#beruang-list-accordion' ) ).toContainText( original, {
			timeout: 10_000,
		} );
		await page
			.locator( '.beruang-transaction-item', { hasText: original } )
			.locator( '.beruang-action-edit' )
			.click();
		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeVisible( { timeout: 5_000 } );

		// Update description and save.
		await page.locator( '#beruang-edit-tx-description' ).fill( updated );
		const putPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions/' ) &&
				resp.request().method() === 'PUT'
		);
		await page.locator( '#beruang-edit-tx-form button[type="submit"]' ).click();
		await putPromise;

		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeHidden( { timeout: 5_000 } );
		await expect( page.locator( '#beruang-list-accordion' ) ).toContainText( updated, {
			timeout: 10_000,
		} );
	} );

	// -------------------------------------------------------------------
	// Delete transaction
	// -------------------------------------------------------------------

	test( 'deleting a transaction removes it from the list', async ( { page, urls } ) => {
		const description = `E2E delete tx ${ Date.now() }`;

		// Create via form.
		await page.goto( urls.form );
		const postPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-description' ).fill( description );
		await page.locator( '#beruang-amount' ).fill( '500' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();
		await postPromise;

		// Open list and delete.
		await page.goto( urls.list );
		await expect( page.locator( '#beruang-list-accordion' ) ).toContainText( description, {
			timeout: 10_000,
		} );

		page.on( 'dialog', ( dialog ) => dialog.accept() );
		const deletePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions/' ) &&
				resp.request().method() === 'DELETE'
		);
		await page
			.locator( '.beruang-transaction-item', { hasText: description } )
			.locator( '.beruang-action-delete' )
			.click();
		await deletePromise;

		await expect( page.locator( '#beruang-list-accordion' ) ).not.toContainText( description, {
			timeout: 10_000,
		} );
	} );

	// -------------------------------------------------------------------
	// Integration: transaction added via form appears in the list
	// -------------------------------------------------------------------

	test( 'a transaction submitted on the form page appears in the list', async ( { page, urls } ) => {
		const description = `E2E list test ${ Date.now() }`;

		// Submit a transaction on the form page and wait for the REST call to succeed.
		await page.goto( urls.form );
		const responsePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-description' ).fill( description );
		await page.locator( '#beruang-amount' ).fill( '5000' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();
		await responsePromise;

		// Navigate to the list page and verify the transaction is visible.
		await page.goto( urls.list );
		await expect( page.locator( '#beruang-list-accordion' ) ).not.toContainText( 'Loading…', {
			timeout: 10_000,
		} );
		await expect( page.locator( '#beruang-list-accordion' ) ).toContainText( description );
	} );
} );
