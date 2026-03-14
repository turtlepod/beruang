import { test, expect } from '@playwright/test';
import { readFileSync } from 'fs';
import { join } from 'path';

const PAGES_FILE = join( __dirname, '../.test-pages.json' );

test.describe( 'unauthenticated access', () => {
	// Override the storage state to simulate a logged-out visitor.
	test.use( { storageState: { cookies: [], origins: [] } } );

	let urls: Record<string, string>;

	test.beforeAll( () => {
		urls = JSON.parse( readFileSync( PAGES_FILE, 'utf-8' ) );
	} );

	const cases: Array<{ key: string; message: string }> = [
		{ key: 'form', message: 'Please log in to add transactions.' },
		{ key: 'list', message: 'Please log in to view transactions.' },
		{ key: 'graph', message: 'Please log in to view graphs.' },
		{ key: 'budget', message: 'Please log in to manage budgets.' },
		{ key: 'wallet', message: 'Please log in to manage wallets.' },
	];

	for ( const { key, message } of cases ) {
		test( `[beruang-${ key }] shows login-required message`, async ( { page } ) => {
			await page.goto( urls[ `${ key }_url` ] );
			await expect( page.locator( '.beruang-login-required' ) ).toBeVisible();
			await expect( page.locator( '.beruang-login-required' ) ).toContainText(
				message
			);
		} );
	}
} );
