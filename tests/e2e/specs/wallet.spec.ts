import { test, expect } from '../fixtures/pages';

test.describe( '[beruang-wallet]', () => {
	test.beforeEach( async ( { page, urls } ) => {
		await page.goto( urls.wallet );
		await page.waitForSelector( '.beruang-wallet-wrapper' );
	} );

	// -------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------

	test( 'renders the wallet wrapper with "Wallets" heading', async ( { page } ) => {
		await expect( page.locator( '.beruang-wallet-wrapper' ) ).toBeVisible();
		await expect( page.locator( '.beruang-section-title' ) ).toHaveText( 'Wallets' );
	} );

	test( 'renders the Add wallet button', async ( { page } ) => {
		await expect( page.locator( '.beruang-wallet-add' ) ).toBeVisible();
	} );

	test( 'renders the wallet list container', async ( { page } ) => {
		await expect( page.locator( '#beruang-wallet-list' ) ).toBeVisible();
	} );

	// -------------------------------------------------------------------
	// Wallet modal
	// -------------------------------------------------------------------

	test( 'wallet modal is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden();
	} );

	test( 'Add wallet button opens the modal', async ( { page } ) => {
		await page.locator( '.beruang-wallet-add' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeVisible();
	} );

	test( 'modal has name, initial-amount, initial-date, and set-as-default fields', async ( { page } ) => {
		await page.locator( '.beruang-wallet-add' ).click();
		await expect( page.locator( '#beruang-wallet-name' ) ).toBeVisible();
		await expect( page.locator( '#beruang-wallet-initial-amount' ) ).toBeVisible();
		await expect( page.locator( '#beruang-wallet-initial-date' ) ).toBeVisible();
		await expect( page.locator( '#beruang-wallet-set-default' ) ).toBeVisible();
	} );

	test( 'initial-date field is pre-filled with today', async ( { page } ) => {
		const today = new Date().toISOString().slice( 0, 10 );
		await page.locator( '.beruang-wallet-add' ).click();
		await expect( page.locator( '#beruang-wallet-initial-date' ) ).toHaveValue( today );
	} );

	test( 'modal × button closes it', async ( { page } ) => {
		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-modal .beruang-modal-close-x' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden();
	} );

	test( 'Cancel button closes the modal', async ( { page } ) => {
		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '.beruang-wallet-cancel-edit' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden();
	} );

	// -------------------------------------------------------------------
	// Integration: create → read
	// -------------------------------------------------------------------

	test( 'creating a wallet shows it in the wallet list', async ( { page } ) => {
		const walletName = `E2E Wallet ${ Date.now() }`;

		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( walletName );
		await page.locator( '#beruang-wallet-initial-amount' ).fill( '100000' );
		await page.locator( '.beruang-wallet-submit-add' ).click();

		// Modal should close and the new wallet appear in the list.
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( {
			timeout: 10_000,
		} );
		await expect( page.locator( '#beruang-wallet-list' ) ).toContainText( walletName, {
			timeout: 10_000,
		} );
	} );

	test( 'newly created wallet shows an edit and delete button', async ( { page } ) => {
		const walletName = `E2E Wallet Edit ${ Date.now() }`;

		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( walletName );
		await page.locator( '.beruang-wallet-submit-add' ).click();

		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( { timeout: 10_000 } );

		const item = page.locator( '.beruang-wallet-card', { hasText: walletName } );
		await expect( item.locator( '.beruang-action-edit' ) ).toBeVisible();
		await expect( item.locator( '.beruang-action-delete' ) ).toBeVisible();
	} );

	test( 'edit button for a wallet opens the modal pre-filled with its data', async ( { page } ) => {
		const walletName = `E2E Wallet Pre ${ Date.now() }`;

		// Create wallet first.
		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( walletName );
		await page.locator( '.beruang-wallet-submit-add' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( { timeout: 10_000 } );

		// Click edit.
		const item = page.locator( '.beruang-wallet-card', { hasText: walletName } );
		await item.locator( '.beruang-action-edit' ).click();

		// Modal should be open with the wallet name pre-filled.
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeVisible();
		await expect( page.locator( '#beruang-wallet-name' ) ).toHaveValue( walletName );
	} );

	test( 'renaming a wallet updates its name in the list', async ( { page } ) => {
		const original = `E2E Rename ${ Date.now() }`;
		const renamed = `E2E Renamed ${ Date.now() }`;

		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( original );
		await page.locator( '.beruang-wallet-submit-add' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( { timeout: 10_000 } );

		// Open edit, change name, save.
		const item = page.locator( '.beruang-wallet-card', { hasText: original } );
		await item.locator( '.beruang-action-edit' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( renamed );
		const putPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/wallets' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '.beruang-wallet-submit-add' ).click();
		await putPromise;

		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( { timeout: 10_000 } );
		await expect( page.locator( '#beruang-wallet-list' ) ).toContainText( renamed, {
			timeout: 10_000,
		} );
		await expect( page.locator( '#beruang-wallet-list' ) ).not.toContainText( original );
	} );

	test( 'deleting a wallet removes it from the list', async ( { page } ) => {
		const walletName = `E2E Delete ${ Date.now() }`;

		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( walletName );
		await page.locator( '.beruang-wallet-submit-add' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( { timeout: 10_000 } );

		page.on( 'dialog', ( dialog ) => dialog.accept() );
		const deletePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/wallets/' ) &&
				resp.request().method() === 'DELETE'
		);
		await page
			.locator( '.beruang-wallet-card', { hasText: walletName } )
			.locator( '.beruang-action-delete' )
			.click();
		await deletePromise;

		await expect( page.locator( '#beruang-wallet-list' ) ).not.toContainText( walletName, {
			timeout: 10_000,
		} );
	} );

	// -------------------------------------------------------------------
	// Transfer button and modal
	// -------------------------------------------------------------------

	test( 'transfer button is always rendered on the page', async ( { page } ) => {
		await expect( page.locator( '.beruang-wallet-transfer-open' ) ).toBeVisible();
	} );

	test( 'transfer modal is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-wallet-transfer-modal' ) ).toBeHidden();
	} );

	test( 'transfer button opens the transfer modal', async ( { page } ) => {
		await page.locator( '.beruang-wallet-transfer-open' ).click();
		await expect( page.locator( '#beruang-wallet-transfer-modal' ) ).toBeVisible();
	} );

	test( 'transfer modal has from-wallet, to-wallet, amount, date and time fields', async ( { page } ) => {
		await page.locator( '.beruang-wallet-transfer-open' ).click();
		await expect( page.locator( '#beruang-transfer-from' ) ).toBeVisible();
		await expect( page.locator( '#beruang-transfer-to' ) ).toBeVisible();
		await expect( page.locator( '#beruang-transfer-amount' ) ).toBeVisible();
		await expect( page.locator( '#beruang-transfer-date' ) ).toBeVisible();
		await expect( page.locator( '#beruang-transfer-time' ) ).toBeVisible();
	} );

	test( 'transfer modal closes via Cancel button', async ( { page } ) => {
		await page.locator( '.beruang-wallet-transfer-open' ).click();
		await page.locator( '.beruang-wallet-transfer-close' ).first().click();
		await expect( page.locator( '#beruang-wallet-transfer-modal' ) ).toBeHidden();
	} );

	test( 'transfer between two wallets succeeds and returns HTTP 200', async ( { page } ) => {
		const walletA = `E2E Transfer A ${ Date.now() }`;
		const walletB = `E2E Transfer B ${ Date.now() }`;

		// Create wallet A.
		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( walletA );
		await page.locator( '#beruang-wallet-initial-amount' ).fill( '100000' );
		await page.locator( '.beruang-wallet-submit-add' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( { timeout: 10_000 } );

		// Create wallet B.
		await page.locator( '.beruang-wallet-add' ).click();
		await page.locator( '#beruang-wallet-name' ).fill( walletB );
		await page.locator( '#beruang-wallet-initial-amount' ).fill( '50000' );
		await page.locator( '.beruang-wallet-submit-add' ).click();
		await expect( page.locator( '#beruang-wallet-modal' ) ).toBeHidden( { timeout: 10_000 } );

		// Open transfer modal and perform transfer.
		await page.locator( '.beruang-wallet-transfer-open' ).click();
		await expect( page.locator( '#beruang-wallet-transfer-modal' ) ).toBeVisible();

		await page.locator( '#beruang-transfer-from' ).selectOption( { label: walletA } );
		await page.locator( '#beruang-transfer-to' ).selectOption( { label: walletB } );
		await page.locator( '#beruang-transfer-amount' ).fill( '10000' );

		const transferPromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/wallets/transfer' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-wallet-transfer-form button[type="submit"]' ).click();
		const response = await transferPromise;
		expect( response.status() ).toBe( 200 );

		await expect( page.locator( '#beruang-wallet-transfer-modal' ) ).toBeHidden( {
			timeout: 10_000,
		} );
	} );
} );
