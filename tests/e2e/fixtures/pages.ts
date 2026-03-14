import { test as base, expect } from '@playwright/test';
import { readFileSync } from 'fs';
import { join } from 'path';

export interface PageUrls {
	form: string;
	list: string;
	graph: string;
	budget: string;
	wallet: string;
}

const PAGES_FILE = join( __dirname, '../.test-pages.json' );

/** Extended test fixture that provides shortcode page URLs. */
export const test = base.extend<{ urls: PageUrls }>( {
	urls: async ( {}, use ) => {
		const data: Record<string, string> = JSON.parse(
			readFileSync( PAGES_FILE, 'utf-8' )
		);
		await use( {
			form: data.form_url,
			list: data.list_url,
			graph: data.graph_url,
			budget: data.budget_url,
			wallet: data.wallet_url,
		} );
	},
} );

export { expect };
