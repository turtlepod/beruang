import { test, expect } from '../fixtures/pages';

/**
 * Modal containment regression tests.
 *
 * Root cause: .beruang-tabs-strip's will-change:transform + translateX()
 * creates a new CSS containing block, trapping position:fixed modals
 * inside overflow:hidden.  The fix (relocateModals) moves every
 * .beruang-modal to document.body and restores the .beruang class so
 * CSS custom properties resolve correctly.
 */

const WRAPPERS: Record< string, string > = {
	form: '.beruang-form-wrapper',
	list: '.beruang-list-wrapper',
	graph: '.beruang-graph-wrapper',
	budget: '.beruang-budget-wrapper',
	wallet: '.beruang-wallet-wrapper',
};

const ALL_TABS = Object.keys( WRAPPERS ) as Array< keyof typeof WRAPPERS >;

// ── Structural invariants (apply regardless of which tab is active) ──

test.describe( '[modals] structural invariants', () => {
	for ( const tab of ALL_TABS ) {
		test( `all .beruang-modal elements are direct children of <body> on the ${ tab } page`, async ( {
			page,
			urls,
		} ) => {
			await page.goto( urls[ tab ] );
			await page.waitForSelector( WRAPPERS[ tab ] );

			const parentTags = await page.evaluate( () =>
				Array.from(
					document.querySelectorAll( '.beruang-modal' ),
					( el ) => el.parentElement?.tagName ?? null
				)
			);

			// Not every tab page renders modals (e.g. graph); skip if none.
			if ( parentTags.length === 0 ) {
				test.skip();
			}

			for ( const tag of parentTags ) {
				expect( tag ).toBe( 'BODY' );
			}
		} );

		test( `every .beruang-modal also carries .beruang class on the ${ tab } page`, async ( {
			page,
			urls,
		} ) => {
			await page.goto( urls[ tab ] );
			await page.waitForSelector( WRAPPERS[ tab ] );

			const missingBeruang = await page.evaluate( () =>
				Array.from( document.querySelectorAll( '.beruang-modal' ) )
					.filter( ( el ) => ! el.classList.contains( 'beruang' ) )
					.map( ( el ) => el.id || '(no id)' )
			);

			expect( missingBeruang ).toEqual( [] );
		} );
	}
} );

// ── Per-modal open / close / styling ──

type ModalSpec = {
	id: string;
	openBtn: string;
	closeBtn: string;
	tab: string;
	/** Optional inner container selector for white-bg check. Default: '.beruang-modal-inner' */
	innerSel?: string;
};

const MODALS: ModalSpec[] = [
	{
		id: '#beruang-calc-modal',
		openBtn: '.beruang-calc-btn',
		closeBtn: '#beruang-calc-modal .beruang-modal-close-x',
		tab: 'form',
		innerSel: '.beruang-calc-content', // calculator uses a different inner wrapper
	},
	{
		id: '#beruang-categories-modal',
		openBtn: '.beruang-manage-categories-btn',
		closeBtn: '.beruang-categories-modal-close',
		tab: 'form',
	},
	{
		id: '#beruang-note-modal',
		openBtn: '.beruang-note-btn',
		closeBtn: '.beruang-note-cancel',
		tab: 'form',
	},
	{
		id: '#beruang-budget-modal',
		openBtn: '.beruang-budget-add',
		closeBtn: '#beruang-budget-modal .beruang-modal-close-x',
		tab: 'budget',
	},
	{
		id: '#beruang-wallet-modal',
		openBtn: '.beruang-wallet-add',
		closeBtn: '#beruang-wallet-modal .beruang-modal-close-x',
		tab: 'wallet',
	},
	{
		id: '#beruang-wallet-transfer-modal',
		openBtn: '.beruang-wallet-transfer-open',
		closeBtn: '.beruang-wallet-transfer-close',
		tab: 'wallet',
	},
];

test.describe( '[modals] open, close, styling', () => {
	for ( const { id, openBtn, closeBtn, tab, innerSel } of MODALS ) {
		const inner = innerSel ?? `${ id } .beruang-modal-inner`;

		test( `${ id } opens and closes on the ${ tab } tab`, async ( {
			page,
			urls,
		} ) => {
			await page.goto( urls[ tab ] );
			await page.waitForSelector( WRAPPERS[ tab ] );

			await page.locator( openBtn ).first().click();
			await expect( page.locator( id ) ).toBeVisible();

			await page.locator( closeBtn ).first().click();
			await expect( page.locator( id ) ).toBeHidden();
		} );

		test( `${ id } overlay has visible background (not transparent)`, async ( {
			page,
			urls,
		} ) => {
			await page.goto( urls[ tab ] );
			await page.waitForSelector( WRAPPERS[ tab ] );

			await page.locator( openBtn ).first().click();
			await expect( page.locator( id ) ).toBeVisible();

			const bgColor = await page.locator( id ).evaluate( ( el ) =>
				window.getComputedStyle( el ).backgroundColor
			);

			expect( bgColor ).not.toMatch(
				/^rgba?\(\s*0,\s*0,\s*0,\s*0\s*\)$/
			);
			expect( bgColor ).not.toBe( 'transparent' );
		} );

		test( `${ id } inner container has a white background`, async ( {
			page,
			urls,
		} ) => {
			await page.goto( urls[ tab ] );
			await page.waitForSelector( WRAPPERS[ tab ] );

			await page.locator( openBtn ).first().click();
			await expect( page.locator( id ) ).toBeVisible();

			await expect( page.locator( inner ).first() ).toBeVisible();

			const bg = await page.locator( inner ).first().evaluate(
				( el ) => window.getComputedStyle( el ).backgroundColor
			);

			expect( bg ).toMatch( /^rgb\(\s*255,\s*255,\s*255\s*\)$/ );
		} );
	}
} );

// ── Edit-transaction modal (needs an existing transaction to click edit) ──

test.describe( '[modals] edit-transaction modal', () => {
	test.beforeEach( async ( { page, urls } ) => {
		await page.goto( urls.list );
		await page.waitForSelector( WRAPPERS.list );

		// Ensure transactions have finished loading.
		await expect( page.locator( '#beruang-list-accordion' ) ).not.toContainText(
			'Loading…',
			{ timeout: 10_000 }
		);
	} );

	test( 'edit modal opens via edit button and closes via Cancel', async ( {
		page,
	} ) => {
		// Find the first transaction row and click its edit button.
		const editBtn = page
			.locator( '.beruang-transaction-item' )
			.first()
			.locator( '.beruang-action-edit' );

		await expect( editBtn ).toBeVisible( { timeout: 5_000 } );
		await editBtn.click();

		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeVisible( {
			timeout: 5_000,
		} );

		// Close via Cancel
		await page.locator( '#beruang-edit-tx-modal .beruang-modal-cancel' ).click();
		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeHidden();
	} );

	test( 'edit modal overlay has visible background', async ( { page } ) => {
		const editBtn = page
			.locator( '.beruang-transaction-item' )
			.first()
			.locator( '.beruang-action-edit' );

		await expect( editBtn ).toBeVisible( { timeout: 5_000 } );
		await editBtn.click();
		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeVisible( {
			timeout: 5_000,
		} );

		const bgColor = await page
			.locator( '#beruang-edit-tx-modal' )
			.evaluate( ( el ) => window.getComputedStyle( el ).backgroundColor );

		expect( bgColor ).not.toMatch( /^rgba?\(\s*0,\s*0,\s*0,\s*0\s*\)$/ );
		expect( bgColor ).not.toBe( 'transparent' );
	} );

	test( 'edit modal inner container has a white background', async ( { page } ) => {
		const editBtn = page
			.locator( '.beruang-transaction-item' )
			.first()
			.locator( '.beruang-action-edit' );

		await expect( editBtn ).toBeVisible( { timeout: 5_000 } );
		await editBtn.click();
		await expect( page.locator( '#beruang-edit-tx-modal' ) ).toBeVisible( {
			timeout: 5_000,
		} );

		const inner = page.locator(
			'#beruang-edit-tx-modal .beruang-modal-inner'
		);
		await expect( inner ).toBeVisible();
		const bg = await inner.evaluate(
			( el ) => window.getComputedStyle( el ).backgroundColor
		);
		expect( bg ).toMatch( /^rgb\(\s*255,\s*255,\s*255\s*\)$/ );
	} );
} );

// ── Closing via overlay click ──

test.describe( '[modals] overlay click closes modal', () => {
	for ( const { id, openBtn, tab } of MODALS ) {
		test( `clicking the overlay of ${ id } closes it`, async ( {
			page,
			urls,
		} ) => {
			await page.goto( urls[ tab ] );
			await page.waitForSelector( WRAPPERS[ tab ] );

			await page.locator( openBtn ).first().click();
			await expect( page.locator( id ) ).toBeVisible();

			// Click the overlay (top-left corner where there's no content).
			await page.locator( id ).click( { position: { x: 5, y: 5 } } );
			await expect( page.locator( id ) ).toBeHidden();
		} );
	}
} );

// ── All modals hidden on load ──

test.describe( '[modals] initial state', () => {
	for ( const tab of ALL_TABS ) {
		test( `no modal is visible on page load on the ${ tab } tab`, async ( {
			page,
			urls,
		} ) => {
			await page.goto( urls[ tab ] );
			await page.waitForSelector( WRAPPERS[ tab ] );

			const visibleModals = await page.evaluate( () =>
				Array.from( document.querySelectorAll( '.beruang-modal' ) )
					.filter( ( el ) => ! ( el as HTMLElement ).hidden )
					.map( ( el ) => el.id )
			);
			expect( visibleModals ).toEqual( [] );
		} );
	}
} );
