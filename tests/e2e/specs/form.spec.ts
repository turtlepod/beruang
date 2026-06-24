import { test, expect } from '../fixtures/pages';

test.describe( '[beruang-form]', () => {
	test.beforeEach( async ( { page, urls } ) => {
		await page.goto( urls.form );
		await page.waitForSelector( '.beruang-form-wrapper' );
	} );

	// -------------------------------------------------------------------
	// Rendering
	// -------------------------------------------------------------------

	test( 'renders the form wrapper and form element', async ( { page } ) => {
		await expect( page.locator( '.beruang-form-wrapper' ) ).toBeVisible();
		await expect( page.locator( '#beruang-transaction-form' ) ).toBeVisible();
	} );

	test( 'date field is pre-filled with today', async ( { page } ) => {
		const today = new Date().toISOString().slice( 0, 10 );
		await expect( page.locator( '#beruang-date' ) ).toHaveValue( today );
	} );

	test( 'description field is empty and has placeholder', async ( { page } ) => {
		const desc = page.locator( '#beruang-description' );
		await expect( desc ).toHaveValue( '' );
		await expect( desc ).toHaveAttribute( 'placeholder', /Meal|Gas/i );
	} );

	test( 'amount field is empty', async ( { page } ) => {
		await expect( page.locator( '#beruang-amount' ) ).toHaveValue( '' );
	} );

	test( 'category select contains Uncategorized option', async ( { page } ) => {
		await expect(
			page.locator( '#beruang-category option[value="0"]' )
		).toHaveText( 'Uncategorized' );
	} );

	// -------------------------------------------------------------------
	// Type toggle
	// -------------------------------------------------------------------

	test( '"Expense" type is active by default', async ( { page } ) => {
		const expenseBtn = page.locator( '.beruang-type-btn' ).filter( { hasText: 'Expense' } );
		await expect( expenseBtn ).toHaveClass( /active/ );
		await expect( page.locator( '#beruang-type' ) ).toHaveValue( 'expense' );
	} );

	test( 'clicking "Income" switches the active type', async ( { page } ) => {
		const incomeBtn = page.locator( '.beruang-type-btn' ).filter( { hasText: 'Income' } );
		await incomeBtn.click();
		await expect( incomeBtn ).toHaveClass( /active/ );
		await expect( page.locator( '#beruang-type' ) ).toHaveValue( 'income' );
	} );

	test( 'clicking "Expense" after "Income" restores expense type', async ( { page } ) => {
		await page.locator( '.beruang-type-btn' ).filter( { hasText: 'Income' } ).click();
		const expenseBtn = page.locator( '.beruang-type-btn' ).filter( { hasText: 'Expense' } );
		await expenseBtn.click();
		await expect( expenseBtn ).toHaveClass( /active/ );
		await expect( page.locator( '#beruang-type' ) ).toHaveValue( 'expense' );
	} );

	// -------------------------------------------------------------------
	// Calculator modal
	// -------------------------------------------------------------------

	test( 'calculator modal is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-calc-modal' ) ).toBeHidden();
	} );

	test( 'calc button opens the calculator modal', async ( { page } ) => {
		await page.locator( '.beruang-calc-btn' ).click();
		await expect( page.locator( '#beruang-calc-modal' ) ).toBeVisible();
	} );

	test( 'calculator modal closes via the × button', async ( { page } ) => {
		await page.locator( '.beruang-calc-btn' ).click();
		await page.locator( '#beruang-calc-modal .beruang-modal-close-x' ).click();
		await expect( page.locator( '#beruang-calc-modal' ) ).toBeHidden();
	} );

	test( 'calculator display starts at zero', async ( { page } ) => {
		await page.locator( '.beruang-calc-btn' ).click();
		await expect( page.locator( '.beruang-calc-display' ) ).toHaveValue( '0' );
	} );

	// -------------------------------------------------------------------
	// Note modal
	// -------------------------------------------------------------------

	test( 'note modal is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-note-modal' ) ).toBeHidden();
	} );

	test( 'note button opens the note modal', async ( { page } ) => {
		await page.locator( '.beruang-note-btn' ).first().click();
		await expect( page.locator( '#beruang-note-modal' ) ).toBeVisible();
	} );

	test( 'note modal cancel button closes it', async ( { page } ) => {
		await page.locator( '.beruang-note-btn' ).first().click();
		await page.locator( '.beruang-note-cancel' ).click();
		await expect( page.locator( '#beruang-note-modal' ) ).toBeHidden();
	} );

	// -------------------------------------------------------------------
	// Categories modal
	// -------------------------------------------------------------------

	test( 'categories modal is hidden on load', async ( { page } ) => {
		await expect( page.locator( '#beruang-categories-modal' ) ).toBeHidden();
	} );

	test( 'manage-categories button opens the categories modal', async ( { page } ) => {
		await page.locator( '.beruang-manage-categories-btn' ).first().click();
		await expect( page.locator( '#beruang-categories-modal' ) ).toBeVisible();
	} );

	test( 'categories modal has a category name input and parent select', async ( { page } ) => {
		await page.locator( '.beruang-manage-categories-btn' ).first().click();
		await expect( page.locator( '#beruang-cat-name' ) ).toBeVisible();
		await expect( page.locator( '#beruang-cat-parent' ) ).toBeVisible();
	} );

	test( 'categories modal closes via the close button', async ( { page } ) => {
		await page.locator( '.beruang-manage-categories-btn' ).first().click();
		await page.locator( '.beruang-categories-modal-close' ).click();
		await expect( page.locator( '#beruang-categories-modal' ) ).toBeHidden();
	} );

	// -------------------------------------------------------------------
	// Form submission
	// -------------------------------------------------------------------

	test( 'submitting a valid transaction returns HTTP 200 from the REST API', async ( { page } ) => {
		const responsePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);

		await page.locator( '#beruang-description' ).fill( 'E2E test expense' );
		await page.locator( '#beruang-amount' ).fill( '10000' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();

		const response = await responsePromise;
		expect( response.status() ).toBe( 200 );
	} );

	test( 'submitting an income transaction returns HTTP 200', async ( { page } ) => {
		const responsePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);

		await page.locator( '.beruang-type-btn' ).filter( { hasText: 'Income' } ).click();
		await page.locator( '#beruang-description' ).fill( 'E2E test income' );
		await page.locator( '#beruang-amount' ).fill( '50000' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();

		const response = await responsePromise;
		const body = await response.json();
		expect( response.status() ).toBe( 200 );
		expect( body.success ).toBe( true );
	} );

	test( 'form description and amount clear after successful submission', async ( { page } ) => {
		const responsePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/transactions' ) &&
				resp.request().method() === 'POST'
		);

		await page.locator( '#beruang-description' ).fill( 'E2E reset test' );
		await page.locator( '#beruang-amount' ).fill( '1234' );
		await page.locator( '#beruang-transaction-form button[type="submit"]' ).click();
		await responsePromise;

		// After save the form should reset.
		await expect( page.locator( '#beruang-description' ) ).toHaveValue( '', { timeout: 5_000 } );
		await expect( page.locator( '#beruang-amount' ) ).toHaveValue( '', { timeout: 5_000 } );
	} );

	// -------------------------------------------------------------------
	// Calculator integration
	// -------------------------------------------------------------------

	test( 'calculator buttons populate the display', async ( { page } ) => {
		await page.locator( '.beruang-calc-btn' ).click();
		// Calculator buttons are rendered dynamically by JS; click digit buttons.
		await page.locator( '.beruang-calc-buttons button' ).filter( { hasText: '1' } ).first().click();
		await page.locator( '.beruang-calc-buttons button' ).filter( { hasText: '5' } ).first().click();
		await page.locator( '.beruang-calc-buttons button' ).filter( { hasText: '0' } ).first().click();
		await expect( page.locator( '.beruang-calc-display' ) ).toHaveValue( '150' );
	} );

	test( '"Insert & Close" applies calculator result to the amount field', async ( { page } ) => {
		await page.locator( '.beruang-calc-btn' ).click();
		await page.locator( '.beruang-calc-buttons button' ).filter( { hasText: '2' } ).first().click();
		await page.locator( '.beruang-calc-buttons button' ).filter( { hasText: '5' } ).first().click();
		await page.locator( '.beruang-calc-insert-close' ).click();
		await expect( page.locator( '#beruang-calc-modal' ) ).toBeHidden();
		await expect( page.locator( '#beruang-amount' ) ).toHaveValue( '25' );
	} );

	test( 'calculator clear button resets display to zero', async ( { page } ) => {
		await page.locator( '.beruang-calc-btn' ).click();
		await page.locator( '.beruang-calc-buttons button' ).filter( { hasText: '9' } ).first().click();
		await page.locator( '.beruang-calc-clear' ).click();
		await expect( page.locator( '.beruang-calc-display' ) ).toHaveValue( '0' );
	} );

	// -------------------------------------------------------------------
	// Note modal integration
	// -------------------------------------------------------------------

	test( 'note text saved in modal syncs to the hidden note textarea', async ( { page } ) => {
		const noteText = 'E2E note content';
		await page.locator( '.beruang-note-btn' ).first().click();
		await page.locator( '#beruang-note-modal-text' ).fill( noteText );
		await page.locator( '.beruang-note-save' ).click();
		await expect( page.locator( '#beruang-note-modal' ) ).toBeHidden();
		// The hidden textarea in the form should now carry the note value.
		const noteValue = await page.locator( '#beruang-note' ).inputValue();
		expect( noteValue ).toBe( noteText );
	} );

	test( 'reopening note modal shows previously saved text', async ( { page } ) => {
		const noteText = 'E2E note reopen';
		await page.locator( '.beruang-note-btn' ).first().click();
		await page.locator( '#beruang-note-modal-text' ).fill( noteText );
		await page.locator( '.beruang-note-save' ).click();

		// Open again and verify text is preserved.
		await page.locator( '.beruang-note-btn' ).first().click();
		await expect( page.locator( '#beruang-note-modal-text' ) ).toHaveValue( noteText );
	} );

	// -------------------------------------------------------------------
	// Category CRUD in modal
	// -------------------------------------------------------------------

	test( 'adding a category in the modal causes it to appear in the form select', async ( { page } ) => {
		const catName = `E2E Cat ${ Date.now() }`;

		await page.locator( '.beruang-manage-categories-btn' ).first().click();
		await expect( page.locator( '#beruang-categories-modal' ) ).toBeVisible();

		// Wait for the categories list to finish loading.
		await expect( page.locator( '.beruang-cat-loading' ) ).toBeHidden( { timeout: 10_000 } );

		const saveResponsePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/categories' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-cat-name' ).fill( catName );
		await page.locator( '.beruang-cat-submit-add' ).click();
		await saveResponsePromise;

		// Close the modal; the new category should appear in the form's category select.
		await page.locator( '.beruang-categories-modal-close' ).click();
		await expect(
			page.locator( '#beruang-category option', { hasText: catName } )
		).toBeAttached( { timeout: 10_000 } );
	} );

	test( 'deleting a category removes it from the categories list', async ( { page } ) => {
		const catName = `E2E Del Cat ${ Date.now() }`;

		// Create the category first.
		await page.locator( '.beruang-manage-categories-btn' ).first().click();
		await expect( page.locator( '.beruang-cat-loading' ) ).toBeHidden( { timeout: 10_000 } );
		const saveResponsePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/categories' ) &&
				resp.request().method() === 'POST'
		);
		await page.locator( '#beruang-cat-name' ).fill( catName );
		await page.locator( '.beruang-cat-submit-add' ).click();
		await saveResponsePromise;

		// Delete the newly created category.
		page.on( 'dialog', ( dialog ) => dialog.accept() );
		const deleteResponsePromise = page.waitForResponse(
			( resp ) =>
				resp.url().includes( '/beruang/v1/categories/' ) &&
				resp.request().method() === 'DELETE'
		);
		await page.locator( '#beruang-categories-list li', { hasText: catName } )
			.locator( '.beruang-action-delete' )
			.click();
		await deleteResponsePromise;

		await expect(
			page.locator( '#beruang-categories-list li', { hasText: catName } )
		).toBeHidden( { timeout: 10_000 } );
	} );
} );
